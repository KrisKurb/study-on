<?php


namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Exception\RegisterException;
use App\Model\UserDto;
use App\Security\User;
use JMS\Serializer\SerializerInterface;

class BillingClient
{
    private $startPath;
    protected $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->startPath = $_ENV['BILLING'];
        $this->serializer = $serializer;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function auth(string $request): array
    {
        // Запрос в сервис
        $inquiry = curl_init($this->startPath . '/api/v1/auth');
        curl_setopt($inquiry, CURLOPT_POST, 1);
        curl_setopt($inquiry, CURLOPT_POSTFIELDS, $request);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($request)
        ]);
        $response = curl_exec($inquiry);
        // Обработка ошибки с биллинга
        if ($response === false) {
            throw new BillingUnavailableException('Попробуйте позже');
        }
        curl_close($inquiry);

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            if ($result['code'] === 401) {
                throw new BillingUnavailableException('Проверьте правильность введёного логина и пароля');
            }
        }
        return $result;
    }

    public function register(UserDto $userTemp): UserDto
    {
        $dataSerialize = $this->serializer->serialize($userTemp, 'json');
        // Запрос в сервис
        $inquiry = curl_init($this->startPath . '/api/v1/register');
        curl_setopt($inquiry, CURLOPT_POST, 1);
        curl_setopt($inquiry, CURLOPT_POSTFIELDS, $dataSerialize);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataSerialize)
        ]);
        $response = curl_exec($inquiry);

        curl_close($inquiry);
        
        // Обработка ошибки с биллинга
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен. 
            Попробуйте зарегистрироваться позднее');
        }
        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            if ($result['code'] == 403) {
                throw new RegisterException($result['message']);
            }

            throw new BillingUnavailableException('Сервис временно недоступен. 
        Попробуйте зарегистрироваться позднее');
        }

        /** @var UserDto $userDto */
        $userDto = $this->serializer->deserialize($response, UserDto::class, 'json');

        return $userDto;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getCurrentUser(User $user, DecodingJwt $decodingJwt)
    {
        $decodingJwt->decoding($user->getApiToken());

        // Запрос в сервис
        $inquiry = curl_init($this->startPath . '/api/v1/users/current');
        curl_setopt($inquiry, CURLOPT_HTTPGET, 1);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user->getApiToken()
        ]);
        $response = curl_exec($inquiry);
        curl_close($inquiry);
        // Обработка ошибки
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен. 
            Попробуйте позднее');
        }

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingUnavailableException($result['message']);
        }

        return $response;
    }
}
