<?php

namespace Plugins\Dashboard;

use Systems\SiteModule;

/**
 * Contoh site class
 */
class Site extends SiteModule
{

    public function init()
    {
        $slug = parseURL();

        if (empty($slug[0])) {
            $this->core->router->changeRoute(HOMEPAGE);
        }

        \Systems\Lib\Event::add('router.notfound', function () {
            $this->get404();
        });

    }

    /**
     * Register module routes
     * Call the appropriate method/function based on URL
     *
     * @return void
     */
    public function routes()
    {
        // Simple:
        $this->route('dashboard', 'getIndex');
    }

    /**
     * GET: /contoh
     * Called method by router
     *
     * @return string
     */
    public function getIndex()
    {

        $this->core->addCSS(url(MODULES.'/dashboard/css/style.css?v={$opensimrs.version}'));
        $this->core->addJS(url(BASE_DIR.'/assets/jscripts/Chart.bundle.min.js'));
        $this->core->addJS(url(MODULES.'/dashboard/js/app.js?v={$opensimrs.version}'));

        $stats['getPasiens'] = number_format($this->countPasien(),0,'','.');
        $stats['getVisities'] = number_format($this->countVisite(),0,'','.');
        $stats['getEmployes'] = number_format($this->countEmploye(),0,'','.');
        $stats['pasienChart'] = $this->pasienChart(15);

        $page = [
            'title' => 'Khanza LITE',
            'desc' => 'Sistem Informasi Rumah Sakit',
            'content' => $this->draw('dashboard.html', ['stats' => $stats])
        ];

        $this->setTemplate('index.html');
        $this->tpl->set('page', $page);
    }

    public function countVisite()
    {
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->oneArray();

        return $record['count'];
    }

    public function countPasien()
    {
        $record = $this->db('pasien')
            ->select([
                'count' => 'COUNT(DISTINCT no_rkm_medis)',
            ])
            ->oneArray();

        return $record['count'];
    }

    public function countEmploye()
    {
        $record = $this->db('pegawai')
            ->select([
                'count' => 'COUNT(DISTINCT nik)',
            ])
            ->where('stts_aktif', '=', 'AKTIF')
            ->orWhere('stts_aktif', '=', 'CUTI')
            ->oneArray();

        return $record['count'];
    }

    public function pasienChart($days = 14, $offset = 0)
    {
        $time = strtotime(date("Y-m-d", strtotime("-".$days + $offset." days")));
        $date = date("Y-m-d", strtotime("-".$days + $offset." days"));

        $query = $this->db('reg_periksa')
            ->select([
              'count'        => 'COUNT(*)',
              'formatedDate' => 'tgl_registrasi',
            ])
            ->where('tgl_registrasi', '>=', $date)
            ->group(['formatedDate'])
            ->asc('formatedDate');


            $data = $query->toArray();

            $return = [
                'labels'  => [],
                'visits'  => [],
            ];

            while ($time < (time() - ($offset * 86400))) {
                $return['labels'][] = '"'.date("Y-m-d", $time).'"';
                $return['readable'][] = '"'.date("d M Y", $time).'"';
                $return['visits'][] = 0;

                $time = strtotime('+1 day', $time);
            }

            foreach ($data as $day) {
                $index = array_search('"'.$day['formatedDate'].'"', $return['labels']);
                if ($index === false) {
                    continue;
                }

                $return['visits'][$index] = $day['count'];
            }

        return $return;
    }

    public function get404()
    {
        $page = [
            'title' => 'Khanza LITE',
            'desc' => 'Sistem Informasi Rumah Sakit',
            'content' => '<div class="container text-center" style="margin-top: 30px;margin-bottom: 30px;"><h1>404 Not Found</h1></div>'
        ];

        $this->setTemplate('index.html');
        $this->tpl->set('page', $page);
    }

}
