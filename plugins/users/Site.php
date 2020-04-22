<?php

namespace Plugins\Users;

use Systems\SiteModule;

class Site extends SiteModule
{
    public function init()
    {
        $this->tpl->set('users', function () {
            $result = [];
            $users = $this->db('users')->select(['id', 'username', 'fullname', 'description', 'avatar', 'email'])->toArray();

            foreach ($users as $key => $value) {
                $result[$value['id']] = $users[$key];
                $result[$value['id']]['avatar'] = url('uploads/users/' . $value['avatar']);
            }
            return $result;
        });
    }
}
