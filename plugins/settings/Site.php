<?php

namespace Plugins\Settings;

use Systems\SiteModule;

class Site extends SiteModule
{
    public function init()
    {
        $this->tpl->set('settings', function () {
            $settings = $this->db('setting')->toArray();
            return $settings[0];
        });
    }
}
