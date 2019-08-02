<?php

use Slim\Http\Request;

/**
 * Class Xhgui_Storage_Filter
 */
class Xhgui_Storage_Filter
{

    /**
     *
     */
    const SORT_WT = 1;

    /**
     *
     */
    const SORT_CPU = 2;

    /**
     *
     */
    const SORT_MU = 3;

    /**
     *
     */
    const SORT_PMU = 4;

    /**
     *
     */
    const SORT_TIME = 5;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $hasSearch = false;

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $perPage = 25;

    /**
     * Xhgui_Storage_Filter constructor.
     */
    public function __construct()
    {
        $this->data = [
            'id'          => null,
            'startDate'   => null,
            'endDate'     => null,
            'url'         => null,
            'method'      => null,
            'sessionId'   => null,
            'controller'  => null,
            'action'      => null,
            'version'     => null,
            'branch'      => null,
            'application' => null,
            'sort'        => null,
            'direction'   => null,
            'cookie'      => null,
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $ret = [];
        foreach ($this->data as $key => $value) {
            if (isset($this->data[$key])) {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * @param $request
     * @return Xhgui_Storage_Filter
     */
    public static function fromRequest(Request $request)
    {
        $instance = new self;

        $instance->setUrl($request->get('url', null));
        $instance->setStartDate($request->get('startDate', null));
        $instance->setEndDate($request->get('endDate', null));

        $instance->setSort($request->get('sort', null));
        $instance->setDirection($request->get('direction', 'desc'));

        $instance->setPage($request->get('page', null));

        $instance->setCookie($request->get('cookie', null));

        return $instance;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->data['id'];
    }

    /**
     * @param string $id
     * @return Xhgui_Storage_Filter
     */
    public function setId($id)
    {
        $this->hasSearch = true;
        $this->data['id'] = $id;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->data['startDate'];
    }

    /**
     * @param DateTime $startDate
     * @return Xhgui_Storage_Filter
     */
    public function setStartDate($startDate)
    {
        if (empty($startDate)) {
            return $this;
        }
        $this->hasSearch = true;

        $this->data['startDate'] = !empty($startDate) ? $startDate : null;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->data['endDate'];
    }

    /**
     * @param DateTime $endDate
     * @return Xhgui_Storage_Filter
     */
    public function setEndDate($endDate)
    {
        if (empty($endDate)) {
            return $this;
        }
        $this->hasSearch = true;

        $this->data['endDate'] = $endDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->data['url'];
    }

    /**
     * @param string $url
     * @return Xhgui_Storage_Filter
     */
    public function setUrl($url)
    {
        if (empty($url)) {
            return $this;
        }
        $this->hasSearch = true;

        $this->data['url'] = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->data['method'];
    }

    /**
     * @param string $method
     * @return Xhgui_Storage_Filter
     */
    public function setMethod($method)
    {
        $this->hasSearch = true;

        $this->data['method'] = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->data['sessionId'];
    }

    /**
     * @param string $sessionId
     * @return Xhgui_Storage_Filter
     */
    public function setSessionId($sessionId)
    {
        $this->hasSearch = true;

        $this->data['sessionId'] = $sessionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->data['controller'];
    }

    /**
     * @param string $controller
     * @return Xhgui_Storage_Filter
     */
    public function setController($controller)
    {
        $this->hasSearch = true;

        $this->data['controller'] = $controller;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->data['action'];
    }

    /**
     * @param string $action
     * @return Xhgui_Storage_Filter
     */
    public function setAction($action)
    {
        $this->hasSearch = true;

        $this->data['action'] = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->data['version'];
    }

    /**
     * @param string $version
     * @return Xhgui_Storage_Filter
     */
    public function setVersion($version)
    {
        $this->hasSearch = true;

        $this->data['version'] = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getBranch()
    {
        return $this->data['branch'];
    }

    /**
     * @param string $branch
     * @return Xhgui_Storage_Filter
     */
    public function setBranch($branch)
    {
        $this->hasSearch = true;

        $this->data['branch'] = $branch;
        return $this;
    }

    /**
     * @return string
     */
    public function getApplication()
    {
        return $this->data['application'];
    }

    /**
     * @param string $application
     * @return Xhgui_Storage_Filter
     */
    public function setApplication($application)
    {
        $this->hasSearch = true;

        $this->data['application'] = $application;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return Xhgui_Storage_Filter
     */
    public function setPage($page)
    {
        if (empty($page)) {
            return $this;
        }
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param int $perPage
     * @return Xhgui_Storage_Filter
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * @return string
     */
    public function getSort()
    {
        return $this->data['sort'];
    }

    /**
     * @param string $sort
     * @return Xhgui_Storage_Filter
     */
    public function setSort($sort)
    {
        if (empty($sort)) {
            return $this;
        }
        $this->data['sort'] = $sort;
        return $this;
    }

    /**
     * @return string
     */
    public function getDirection()
    {
        return $this->data['direction'];
    }

    /**
     * @param string $direction
     * @return Xhgui_Storage_Filter
     */
    public function setDirection($direction)
    {
        if (empty($direction)) {
            return $this;
        }
        $this->data['direction'] = $direction;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getCookie()
    {
        return $this->data['cookie'];
    }

    /**
     * @param string $cookie
     * @return Xhgui_Storage_Filter
     */
    public function setCookie($cookie)
    {
        if (empty($cookie)) {
            return $this;
        }
        $this->data['cookie'] = $cookie;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSearch()
    {
        return $this->hasSearch;
    }

    /**
     * @param bool $hasSearch
     * @return Xhgui_Storage_Filter
     */
    public function setHasSearch($hasSearch)
    {
        $this->hasSearch = $hasSearch;
        return $this;
    }
}
