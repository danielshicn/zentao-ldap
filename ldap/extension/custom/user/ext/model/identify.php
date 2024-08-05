<?php
public function identify($account, $password)
{
    if (0 == strcmp('$',substr($account, 0, 1))) {
        return parent::identify(ltrim($account, '$'), $password);
    } else {
        $user = new stdclass();
        $ldap = $this->loadModel('ldap');
        $ldapConfig = $this->config->ldap;
        $ldapUser = $ldap->getUser($ldapConfig, $account);
        // 找到ldap用户
        if ($ldapUser) {
            // ldap密码认证
            $pass = $ldap->identify($ldapConfig->host, $ldapUser['dn'], $password);
            if (0 == strcmp('Success', $pass)) {
                $record = $this->dao->select('*')->from(TABLE_USER)
                    ->where('deleted')->eq(0)
                    ->andWhere('ldap_account')->eq($account)
                    ->fetch();
                if($record != null && $record->account != null) {
                    $user = $record;
                }
                // 根据ldap信息，更新数据库用户信息
                $user->ldap_account = $ldapUser[$i][$ldapConfig->uid][0];
                $user->email = $ldapUser[$i][$ldapConfig->mail][0];
                $user->realname = $ldapUser[$i][$ldapConfig->name][0];
                $user->ip =  $this->server->remote_addr;
                $user->last = date(DT_DATETIME1,$this->server->request_time);

                if($record != null && $record->account!=null) {
                    // 已存在，更新数据用户
                    $user->visits = $user->visits + 1;
                    $this->dao->update(TABLE_USER)
                        ->data($user)
                        ->where('ldap_account')->eq($user->ldap_account)
                        ->autoCheck()
                        ->exec();
                } else {
                    // 不存在，创建数据用户
                    $user->account = $user->ldap_account;
                    $user->visits = 0;
                    // 插入数据库用户
                    $this->dao->insert(TABLE_USER)->data($user)->autoCheck()->exec();
                    // 再次获取数据库用户
                    $user = $this->dao->select('*')->from(TABLE_USER)
                        ->where('deleted')->eq(0)
                        ->andWhere('ldap_account')->eq($account)
                        ->fetch();
                }
                
                // 禅道有多处地方需要二次验证密码, 所以需要保存密码的 MD5 在 session 中以供后续验证
                $user->password = md5($password);
                // 判断用户是否来自 ldap
                $user->fromldap = true;

                /* Create cycle todo in login. */
                $todoList = $this->dao->select('*')->from(TABLE_TODO)
                    ->where('cycle')->eq(1)
                    ->andWhere('account')->eq($user->account)
                    ->fetchAll('id');
                $this->loadModel('todo')->createByCycle($todoList);
            }
        }
        return $user;
    }
}