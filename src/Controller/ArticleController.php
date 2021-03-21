<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Service\MarkdownHelper;
use Doctrine\ORM\EntityManagerInterface;
use Nexy\Slack\Client;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * Class ArticleController
 */
class ArticleController extends AbstractController
{

    /** @var bool */
    private $isDebug;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * ArticleController constructor.
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param bool $isDebug
     */
    public function __construct(EntityManagerInterface $entityManager,LoggerInterface $logger, bool $isDebug)
    {
        $this->isDebug = $isDebug;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="app_homepage")
     * @return Response
     */
    public function homepage(ArticleRepository $articleRepository)
    {
        $articles = $articleRepository->findAll();
        $articles = $articleRepository->findBy([], ['publishedAt' => 'desc']);
        $articles = $articleRepository->findAllPublishedOrderedByNewest();

        return $this->render('article/homepage.html.twig',
            [
                'articles' => $articles
            ]
        );
    }

    /**
     * @Route("/news/whyowhy/{slug}", name="article_show")
     * @param Article $article
     * @return Response
     * @throws InvalidArgumentException
     * @var MarkdownHelper $markdown
     */
    public function show(Article $article, MarkdownHelper $markdown)
    {
        $comments = $article->getComments();

        $articleContent =
<<<EOF
Spicy **jalapeno** bacon ipsum dolor amet veniam shank in dolore. Ham hock nisi landjaeger cow,
lorem proident [beef ribs](https://bacon.ipsum.com/) aute enim veniam ut cillum pork chuck picanha. Dolore reprehenderit
labore minim pork belly spare ribs cupim short loin in. Elit exercitation eiusmod dolore cow
turkey shank eu *pork* belly meatball non cupim. jammy    
EOF;

        $articleContent = $markdown->parse($article->getContent());

        return $this->render('article/show.html.twig',
            [
                'title' => ucwords(str_replace('_', ' ', $article->getSlug())),
                'slug' => $article->getSlug(),
                'articleContent' => $articleContent,
                'article' => $article,
                'comments' => $comments,
                'twigtestvalue' => 'twigtestvalue'
        ]);
    }

    /**
     * @Route("/news/{slug}/heart", name="article_toggle_heart", methods={"POST"})
     * @param string $slug
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    public function toggleArticleHeart(Article $article, Request $request)
    {
        $article->incrementHeartCount();

        $this->entityManager->persist($article);
        $this->entityManager->flush();;

        // ToDo - actualyu heart/unheart the article

        $this->logger->info('Article is being hearted');
        return new JsonResponse(['hearts' => $article->getHeartCount()]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/admin/article/new", name="admin_article_new")
     */
    public function new()
    {
        die('to do');
    }
}