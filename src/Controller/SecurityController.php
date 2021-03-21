<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController
 */
class SecurityController extends AbstractController
{
    /**
     * @var AuthenticationUtils
     */
    private $authenticationUtils;
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        AuthenticationUtils $authenticationUtils,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager

    )
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login()
    {
        // get the login error if there is one
        $error = $this->authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/register", name="app_register")
     * @param Request $request
     */
    public function register(Request $request)
    {
        $registrationState = false;

        $csrfToken = new CsrfToken('loginform', $request->request->get('_csrf_token'));
        if ($request->getMethod()==='POST') {
            if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
                throw new \Exception('Form is not valid, someone messed with it');
            }

            $user = $this->createUser($request);
            $person = $this->createPerson($request, $user);

            $this->entityManager->persist($user);
            $this->entityManager->persist($person);
            $this->entityManager->flush();

            $registrationState = 'succes';
        }

        return $this->render('security/register.html.twig',[
            'registrationstate' => $registrationState
        ]);
    }

    /**
     * @param Request $request
     * @return User
     */
    public function createUser(Request $request)
    {
        $user = new User();
        $user->setEmail($request->request->get('email'));
        $user->setFirstName($request->request->get('firstname'));
        $passwordHashed = $this->passwordEncoder->encodePassword($user, $request->request->get('password'));
        $user->setPassword($passwordHashed);

        return $user;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return Person
     */
    public function createPerson($request, $user)
    {
        /** Create person */
        $person = new Person();
        $person->setFirstName($request->request->get('firstname'));
        $person->setLastName($request->request->get('lastname'));
        $person->setStreetName($request->request->get('streetname'));
        $person->setStreetNumber($request->request->get('streetnumber'));
        $person->setZipcode($request->request->get('zipcode'));
        /** Add User to Person */
        $person->setUser($user);

        return $person;
    }

    /**
     * Route needed for symfony to be able to logout
     * It does nog do anything
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {

    }
}
