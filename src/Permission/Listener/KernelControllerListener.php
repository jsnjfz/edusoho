<?php
namespace Permission\Listener;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Topxia\Service\Common\AccessDeniedException;
use Topxia\Service\Common\ServiceKernel;

class KernelControllerListener
{
    protected $paths;

    public function __construct(Container $container, $paths)
    {
        $this->container = $container;
        $this->paths     = $paths;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request     = $event->getRequest();
        $route       = $request->attributes->get('_route');
        $permissions = $this->container
            ->get('router')
            ->getRouteCollection()
            ->get($route)
            ->getPermissions();

        $requestPath = $request->getPathInfo();
        $currentUser = ServiceKernel::instance()->getCurrentUser();
        foreach ($this->paths as $key => $path) {
            if (preg_match($path, $requestPath)
                && !empty($permissions)
                && !in_array('ROLE_SUPER_ADMIN', $currentUser['roles'])) {

                foreach ($permissions as $permission) {
                    if($this->container->get('permission.twig.permission_extension')->hasPermission($permission)){
                        return;
                    }
                }
                throw new AccessDeniedException('无权限访问！');
            }
        }
    }
}
