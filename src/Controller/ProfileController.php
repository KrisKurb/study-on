<?php


namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Model\UserDto;
use App\Service\BillingClient;
use App\Service\DecodingJwt;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    private $billingClient;
    private $decodingJwt;
    private $serializer;

    public function __construct(
        BillingClient $billingClient,
        DecodingJwt $decodingJwt,
        SerializerInterface $serializer
    ) {
        $this->billingClient = $billingClient;
        $this->decodingJwt = $decodingJwt;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/", name="profile_user")
     * @throws \Exception
     */
    public function index(): Response
    {
        try {
            $response = $this->billingClient->getCurrentUser($this->getUser(), $this->decodingJwt);
        } catch (BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }
        $userDto = $this->serializer->deserialize($response, UserDto::class, 'json');

        return $this->render('profile/index.html.twig', [
            'userDto' => $userDto,
        ]);
    }
}
