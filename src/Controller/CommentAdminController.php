<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN_COMMENT")
 * Class CommentAdminController
 */
class CommentAdminController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PaginatorInterface */
    private $paginator;

    /**
     * CommentAdminController constructor.
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     */
    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/admin/comment", name="comment_admin")
     */
    public function index(Request $request)
    {
        // Posible to deny acces to this entity, but @IsGranted annotation is an beter option
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $searQuery = $request->query->get('searchquery');
        $commentRepository = $this->entityManager->getRepository(Comment::class);
        $query = $commentRepository->getWithSearchQueryBuilder($searQuery);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('comment_admin/index.html.twig', [
            'pagination' => $pagination,
            'searQuery' => $searQuery
        ]);
    }
}
