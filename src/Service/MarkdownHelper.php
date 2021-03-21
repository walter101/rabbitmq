<?php


namespace App\Service;


use Michelf\MarkdownInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Security\Core\Security;

class MarkdownHelper
{

    /** @var MarkdownInterface  */
    private $markdown;

    /** @var AdapterInterface  */
    private $cache;

    /** @var LoggerInterface $logger */
    private $logger;
    /**
     * @var Security
     */
    private $security;

    /**
     * MarkdownHelper constructor.
     * @param MarkdownInterface $markdown
     * @param AdapterInterface $cache
     */
    public function __construct(MarkdownInterface $markdown, AdapterInterface $cache, LoggerInterface $markdownLogger, Security $security)
    {
        $this->markdown = $markdown;
        $this->cache = $cache;
        $this->logger = $markdownLogger;
        $this->security = $security;
    }

    /**
     * @param string $articleContent
     * @return string
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function parse(string $articleContent)
    {
        /** $item is een referentie bestaande OF niet bestaande plek in het cache systeemn */
        $item = $this->cache->getItem('markdown'.md5($articleContent));

        /**
         * Als isHit false is
         * Dan moet je de plek nog vullen in het cache systeem
         * set() de cached message
         * save() in het cache systeem
         */
        if (!$item->isHit()) {
            $item->set($this->markdown->transform($articleContent));
            $this->cache->save($item);

            $this->logger->info('Saved article in the cache using key: '. $item->getKey(), [
                'user' => $this->security->getUser()
            ]);
        } else {
            $this->logger->info('Fetched article from the cache using key: markdown'.md5($articleContent));
        }

        return $item->get();
    }
}