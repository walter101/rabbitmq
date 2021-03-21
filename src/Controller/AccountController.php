<?php

namespace App\Controller;

use App\Entity\User;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @IsGranted("ROLE_USER")
 */
class AccountController extends AbstractController
{
    /** @var LoggerInterface */
    private $logger;

    /** @var Serializer */
    private $serializer;

    /**
     * AccountController constructor.
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/account", name="app_account")
     */
    public function index()
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->logger->info('Checked account for user-mail: '. $user->getEmail());

        return $this->render('account/login.html.twig', [

        ]);
    }

    /**
     * @Route("/api/account", name="api_account ")
     */
    public function accountApi()
    {
        $user = $this->getUser();

        $userdata = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'firstName' => $user->getFirstName(),
            'password' => $user->getPassword(),
            'twitterusername' => $user->getTwitterUsername(),
        ];

        $serialized = $this->serializer->serialize($userdata, 'json');
        return new JsonResponse($serialized, 200, ['Content-Type' => 'application/json'], true);
    }
}
