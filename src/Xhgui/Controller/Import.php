<?php

use Slim\Slim;

class Xhgui_Controller_Import extends Xhgui_Controller
{

    /**
     * @var Xhgui_Storage_Factory
     */
    protected $storageFactory;

    /**
     * @var Xhgui_Saver
     */
    protected $saver;

    /**
     * Xhgui_Controller_Import constructor.
     * @param Slim $app
     */
    public function __construct(Slim $app, Xhgui_Storage_Factory $storageFactory, Xhgui_Saver $saver)
    {
        parent::__construct($app);
        $this->setStorageFactory($storageFactory);
        $this->setSaver($saver);
    }

    /**
     * Import main page. Use to select source and target.
     */
    public function index()
    {

        $settings = $this->app->container->get('settings');

        $handlers = array();
        if (!empty($settings['save.handler.path'])) {
            $handlers[] = 'file';
        }

        if (!empty($settings['save.handler.upload.uri'])) {
            $handlers[] = 'upload';
        }

        if (!empty($settings['db.host']) && strpos($settings['db.host'], 'mongodb') !== false) {
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
        $reader = $this->getStorageFactory()->create($readConfig);

        // get save handler:
        $saveHandlerConfig['save.handler'] = $target;
        $saver = $this->getSaver()->create($saveHandlerConfig);

        try {
            $filter = new Xhgui_Storage_Filter();
            $page = 0;
            $filter->setPage($page);
            do {
                $allRows = $reader->find($filter);
                foreach ($allRows as $row) {
                    $saver->save($row);
                }

                $filter->setPage($page++);
            } while (count($allRows) > 0);
            
            $this->app->flash('success', 'Import successful');
        } catch (Exception $e) {
            $this->app->flash('failure', 'Import failed');
        }

        $this->app->redirect($this->app->urlFor('import'));
    }

    /**
     * @return Xhgui_Storage_Factory
     */
    public function getStorageFactory()
    {
        return $this->storageFactory;
    }

    /**
     * @param Xhgui_Storage_Factory $storageFactory
     */
    public function setStorageFactory($storageFactory)
    {
        $this->storageFactory = $storageFactory;
    }

    /**
     * @return Xhgui_Saver
     */
    public function getSaver()
    {
        return $this->saver;
    }

    /**
     * @param Xhgui_Saver $saver
     */
    public function setSaver($saver)
    {
        $this->saver = $saver;
    }
}
