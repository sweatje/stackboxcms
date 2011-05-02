<?php
namespace Module\User;
use Stackbox;
use Alloy;

/**
 * User Module
 */
class Controller extends Stackbox\Module\ControllerAbstract
{
    protected $_path = __DIR__;

    /**
     * Access control
     */
    public function init($action = null)
    {
        // Ensure user has rights to create new user account
        $access = false;
        $user = $this->kernel->user();
        if($user && $user->isAdmin()) {
            // If user has admin access
            $access = true;
        } else {
            // If there are not currently any users that exist, allow access to create a new one
            $userCount = $this->kernel->mapper()->all('Module\User\Entity')->count();
            if($userCount == 0) {
                $access = true;
            }
        }
        
        if(!$access) {
            throw new Alloy\Exception\Auth("User is not logged in or does not have proper permissions to perform requested action");
        }
        
        return parent::init();
    }
    
    
    /**
     * Index listing
     * @method GET
     */
    public function indexAction($request)
    {
        return false;
    }


    /**
     * List users for editing or adding
     * @method GET
     */
    public function editlistAction($request, $page, $module)
    {
        $users = $this->kernel->mapper()->all('Module\User\Entity');

        return $this->template(__FUNCTION__)
            ->set(compact('users', 'request', 'page', 'module'))->content();
    }
    
    
    /**
     * Create new user
     * @method GET
     */
    public function newAction($request, $page, $module)
    {
        // Item URL
        $itemUrl = $this->kernel->url(array('page' => $page->url, 'module_name' => $module->name, 'module_id' => (int) $module->id), 'module');

        return $this->formView()
            ->method('post')
            ->action($itemUrl);
    }
    
    
    /**
     * @method GET
     */
    public function editAction($request, $page, $module)
    {
        $user = $this->kernel->mapper()->get('Module\User\Entity', (int) $request->module_item);
        if(!$user) {
            return false;
        }

        // Item URL
        $itemUrl = $this->kernel->url(array('page' => $page->url, 'module_name' => $module->name, 'module_id' => (int) $module->id, 'module_item' => $user->id), 'module_item');

        $form = $this->formView()
            ->action($itemUrl)
            ->method('put')
            ->data($user->dataExcept(array('site_id', 'password', 'salt')));
        return $form;
    }
    
    
    /**
     * Create a new resource with the given parameters
     * @method POST
     */
    public function postMethod($request, $page, $module)
    {
        $mapper = $this->kernel->mapper();
        $item = $mapper->create('Module\User\Entity', $request->post());

        // Overwrite site_id to ensure this is for same site
        $item->site_id = $page->site_id;
        
        // Attempt save
        if($mapper->save($item)) {
            $itemUrl = $this->kernel->url(array('page' => $page->url, 'module_name' => $module->name, 'module_id' => (int) $module->id, 'module_action' => 'editlist'), 'module');
            if($request->format == 'html') {
                return $this->editlistAction($request, $page, $module);
            } else {
                return $this->kernel->resource($item)
                    ->status(201)
                    ->location($itemUrl);
            }
        } else {
            return $this->formView()
                ->status(400)
                ->errors($mapper->errors())
                ->data($request->post());
        }
    }
    
    
    /**
     * Edit existing entry
     * @method PUT
     */
    public function putMethod($request, $page, $module)
    {
        $mapper = $this->kernel->mapper();
        $item = $mapper->get('Module\User\Entity', (int) $request->module_item);
        if(!$item) {
            return false;
        }
        $item->data($request->post());

        // Overwrite site_id to ensure this is for same site
        $item->site_id = $page->site_id;
        
        // Attempt save
        if($mapper->save($item)) {
            $itemUrl = $this->kernel->url(array('page' => $page->url, 'module_name' => $module->name, 'module_id' => (int) $module->id, 'module_action' => 'editlist'), 'module');
            if($request->format == 'html') {
                return $this->editlistAction($request, $page, $module);
            } else {
                return $this->kernel->resource($item)
                    ->status(201)
                    ->location($itemUrl);
            }
        } else {
            return $this->formView()
                ->status(400)
                ->errors($mapper->errors())
                ->data($request->post());
        }
    }
    
    
    /**
     * @method DELETE
     */
    public function deleteMethod($request, $page, $module)
    {
        $item = $this->kernel->mapper->get('Module\User\Entity', $request->module_item);
        if(!$item) {
            return false;
        }
        return $this->kernel->mapper()->delete($item);
    }
    
    
    /**
     * Install Module
     *
     * @see \Stackbox\Module\ControllerAbstract
     */
    public function install($action = null, array $params = array())
    {
        $this->kernel->mapper()->migrate('Module\User\Entity');
        $this->kernel->mapper()->migrate('Module\User\Session\Entity');
        return parent::install($action, $params);
    }
    
    
    /**
     * Uninstall Module
     *
     * @see \Stackbox\Module\ControllerAbstract
     */
    public function uninstall()
    {
        $this->kernel->mapper()->dropDatasource('Module\User\Entity');
        $this->kernel->mapper()->dropDatasource('Module\User\Session\Entity');
        return parent::uninstall();
    }
    
    
    /**
     * Return view object for the add/edit form
     */
    protected function formView()
    {
        return $this->kernel->spotForm('Module\User\Entity')
            ->removeFields(array('site_id', 'salt'));
    }
}