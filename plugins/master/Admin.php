<?php

namespace Plugins\Master;

use Systems\AdminModule;

/**
 * Sample admin class
 */
class Admin extends AdminModule
{
    /**
     * Module navigation
     * Items of the returned array will be displayed in the administration sidebar
     *
     * @return array
     */
    public function navigation()
    {
        return [
            'Index' => 'index',
            'Poliklinik' => 'poliklinik',
            'Dokter' => 'dokter',
            'Data Barang' => 'databarang',
            'Inventaris' => 'inventaris',
            'ICD 9' => 'icd9',
            'ICD 10' => 'icd10',
            'Jenis Perawatan' => 'jns_perawatan',
        ];
    }

    /**
     * GET: /admin/sample/index
     * Subpage method of the module
     *
     * @return string
     */
    public function getIndex()
    {
        $text = 'Hello World';
        return $this->draw('index.html', ['text' => $text]);
    }
}
