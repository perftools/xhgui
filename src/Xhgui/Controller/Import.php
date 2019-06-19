<?php

use Slim\Slim;

class Xhgui_Controller_Import extends Xhgui_Controller
{
    /**
     * @var Xhgui_Profiles
     */
    private $profiles;

    /**
     * Xhgui_Controller_Import constructor.
     * @param Slim $app
     * @param Xhgui_Profiles $profiles
     */
    public function __construct(Slim $app, Xhgui_Profiles $profiles)
    {
        parent::__construct($app);
        $this->setProfiles($profiles);
    }

    /**
     * Import main page. Use to select source and target.
     */
    public function index()
    {

        $settings = $this->app->container->get('settings');

        $handlers = [];
        if (!empty($settings['save.handler.filename'])) {
            $handlers[] = 'file';
        }

        if (!empty($settings['save.handler.upload.uri'])) {
            $handlers[] = 'upload';
        }

        if (!empty($settings['db.host']) && strpos($settings['db.host'], 'mongodb')) {
            $handlers[] = 'mongodb';
        }
        if (!empty($settings['db.dsn'])) {
            $handlers[] = 'pdo';
        }
        $this->_template = 'import/index.twig';
        $this->set(array(
            'base_url'              => 'home',
            'configured_handlers'   => $handlers,
            'status'                => $this->app->flashData()
        ));
    }

    /**
     * Main import function. It does all the work.
     */
    public function import()
    {
        $request = $this->app->request();
        $this->_template = '';
        
        $readConfig         = $this->app->container->get('settings');
        $saveHandlerConfig  = $this->app->container->get('settings');
        $source             = $request->post('source');
        $target             = $request->post('target');

        // get data to import
        $readConfig['save.handler'] = $source;
        $reader = Xhgui_Storage_Factory::factory($readConfig);

        // get save handler:
        $saveHandlerConfig['save.handler'] = $target;
        $saver = Xhgui_Saver::factory($saveHandlerConfig);


        try {
            $filter = new Xhgui_Storage_Filter();
            $page = 0;
            $filter->setPage($page);
            do {
                $allRows = $reader->find($filter);
                foreach ($allRows as $row) {
                    $row['meta']['application'] = 'test';
                    $row['meta']['version'] = 'test';
                    $row['meta']['branch'] = 'test';
                    $row['meta']['controller'] = 'test';
                    $row['meta']['action'] = 'test';
                    $row['meta']['session_id'] = 'test';
                    $saver->save($row);
                }

                $filter->setPage($page++);
            } while ($allRows->count() > 0);
            
            $this->app->flash('success', 'Import successful');
        } catch (Exception $e) {
            $this->app->flash('failure', 'Import failed');
        }

        $this->app->redirect($this->app->urlFor('import'));
    }

    /**
     * @return Xhgui_Profiles
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @param Xhgui_Profiles $profiles
     */
    public function setProfiles($profiles)
    {
        $this->profiles = $profiles;
    }
}
