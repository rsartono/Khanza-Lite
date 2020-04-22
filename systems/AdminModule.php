<?php

namespace Systems;

/**
 * Admin class for administration panel
 */
abstract class AdminModule extends BaseModule
{
    /**
     * Module navigation
     *
     * @return array
     */
    public function navigation()
    {
        return [];
    }
}
