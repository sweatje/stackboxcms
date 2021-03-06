<?php
namespace Module\Page\Module;
use Stackbox;

/**
 * Page module controller - Add, move, or delete modules
 */
class Controller extends Stackbox\Module\ControllerAbstract
{
    /**
     * Module listing
     * @method GET
     */
    public function indexAction($request, $page, $module)
    {
        return false;
    }
    
    
    /**
     * @method GET
     */
    public function newAction($request, $page, $module)
    {
        return $this->formView()
            ->method('post')
            ->action($this->kernel->url(array('page' => '/'), 'page'));
    }
    
    
    /**
     * @method GET
     */
    public function editAction($request, $page, $module)
    {
        $kernel = $this->kernel;
        
        return $this->formView();
    }
    
    
    /**
     * Create a new resource with the given parameters
     * @method POST
     */
    public function postMethod($request, $page, \Module\Page\Module\Entity $module)
    {
        $kernel = $this->kernel;
        
        // @todo Attempt to load module before saving it so we know it will work
        
        // Save it
        $mapper = $kernel->mapper();
        $entity = $mapper->get('Module\Page\Module\Entity')
            ->data($request->post() + array(
                'site_id' => $page->site_id,
                'page_id' => $page->id,
                'date_created' => new \DateTime()
            ));
        if($mapper->save($entity)) {
            $pageUrl = $this->kernel->url(array('page' => $page->url), 'page');
            if($request->format == 'html') {
                // Set module data for return content
                $module->data($entity->data());
                // Dispatch to return module content
                return $kernel->dispatch($entity->name, 'indexAction', array($request, $page, $entity));
            } else {
                return $this->kernel->resource($entity)
                    ->created($pageUrl);
            }
        } else {
            $this->kernel->response(400);
            return $this->formView()
                ->data($request->post())
                ->errors($mapper->errors());
        }
    }
    
    
    /**
     * @method GET
     */
    public function deleteAction($request, $page, $module)
    {
        if($request->format == 'html') {
            $view = new \Alloy\View\Generic\Form('form');
            $form = $view
                ->method('delete')
                ->action($this->kernel->url(array('page' => '/', 'module_name' => $this->urlName(), 'module_id' => 0, 'module_item' => $request->module_item), 'module_item'))
                ->data(array('item_id' => $request->module_item))
                ->submit('Delete');
            return "<p>Are you sure you want to delete this module?</p>" . $form;
        }
        return false;
    }
    
    
    /**
     * @method DELETE
     */
    public function deleteMethod($request, $page, $module)
    {
        $item = $this->kernel->mapper()->get('Module\Page\Module\Entity', $request->module_item);
        if($item) {
            $this->kernel->mapper()->delete($item);
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Save module sorting
     * @method POST
     */
    public function saveSortAction($request, $page, $module)
    {
        if($request->modules && is_array($request->modules)) {
            $mapper = $this->kernel->mapper();
            foreach($request->modules as $regionName => $modules) {
                foreach($modules as $orderIndex => $moduleId) {
                    $item = $mapper->get('Module\Page\Module\Entity', $moduleId);
                    if($item) {
                        $item->region = $regionName;
                        $item->ordering = $orderIndex;
                        $mapper->save($item);
                    }
                }
            }
        }
        return true;
    }
    
    
    /**
     * Return view object for the add/edit form
     */
    protected function formView()
    {
        $view = $this->kernel->spotForm('Module\Page\Module\Entity')
            ->removeFields(array('id', 'date_created', 'date_modified'));
        return $view;
    }
    
    
    /**
     * Install Module
     *
     * @see \Stackbox\Module\ControllerAbstract
     */
    public function install($action = null, array $params = array())
    {
        $this->kernel->mapper()->migrate('Module\Page\Module\Entity');
        return parent::install($action);
    }
    
    
    /**
     * Uninstall Module
     *
     * @see \Stackbox\Module\ControllerAbstract
     */
    public function uninstall()
    {
        return $this->kernel->mapper()->dropDatasource('Module\Page\Module\Entity');
    }
}