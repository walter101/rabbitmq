<?php

namespace App\Twig;

use App\Service\MarkdownHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements ServiceSubscriberInterface
{

    /**
     * Minicontainer object
     * @var ContainerInterface
     */
    private $container;

    /**
     * Create a (mini) container in wich the dependencies for this twig extention are stored
     * Doing this prevents symfony to instantiate dependencies used in this tiwg extention/filter
     * AppExtension constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('wigit', [$this, 'doSomething']),
        ];
    }

    /**
     * wigdet is the name of the filter possiblily used in tiwg:   sometext|widget
     * doSomething is the method that will beused HERE to edit the $value on wich the filter is performed
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wigit', [$this, 'doSomething']),
        ];
    }

    /**
     * Actualy method wich is used to edit the text in twig
     * Value if the value on wich this method will perform some action
     * After this the $value will bve returned
     * @param $value
     * @return string
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function doSomething($value)
    {
        return $this->container->get(MarkdownHelper::class)->parse($value)."|fuckme ";
    }

    /**
     * implements ServiceSubscriberInterface
     * This is the method in wich we store all the services (dependencies) who will be used to execute this filter
     * @return array
     */
    public static function getSubscribedServices()
    {
        return [
            MarkdownHelper::class
        ];
    }
}
