<?php


namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Exception\RegisterException;
use App\Model\CourseDto;
use App\Model\PayDto;
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
    public function auth(string $request): UserDto
    {
        // Запрос в сервис биллинг
        $query = curl_init($this->startPath . '/api/v1/auth');
        curl_setopt($query, CURLOPT_POST, 1);
        curl_setopt($query, CURLOPT_POSTFIELDS, $request);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($query, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($request)
        ]);
        $response = curl_exec($query);
        // Ошибка с биллинга
        if ($response === false) {
            throw new BillingUnavailableException('Возникли технические неполадки. Попробуйте позднее');
        }
        curl_close($query);

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            if ($result['code'] === 401) {
                throw new BillingUnavailableException('Проверьте правильность введёного логина и пароля');
            }
        }
        /** @var UserDto $userDto */
        $userDto = $this->serializer->deserialize($response, UserDto::class, 'json');

        return $userDto;
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

    public function refreshToken(string $refreshToken): UserDto
    {
        $userDto = new UserDto();
        $userDto->setRefreshToken($refreshToken);
        $data = $this->serializer->serialize($userDto, 'json');

        // Запрос в сервис
        $inquiry = curl_init($this->startPath. '/api/v1/token/refresh');
        curl_setopt($inquiry, CURLOPT_POST, 1);
        curl_setopt($inquiry, CURLOPT_POSTFIELDS, $data);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $response = curl_exec($inquiry);
        // Обработка ошибки
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен.
            Попробуйте авторизоваться позднее');
        }
        curl_close($inquiry);

        /** @var UserDto $userDto */
        $userDto = $this->serializer->deserialize($response, UserDto::class, 'json');

        return $userDto;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getAllCourses(): array
    {
        $inquiry = curl_init($this->startPath . '/api/v1/courses');
        curl_setopt($inquiry, CURLOPT_HTTPGET, 1);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $response = curl_exec($inquiry);
        // Ошибка с биллинга
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен. 
            Попробуйте позднее');
        }
        curl_close($inquiry);

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingUnavailableException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array<App\Model\CourseDto>', 'json');
    }

    /**
     * @throws BillingUnavailableException
     */
    public function transactions(User $user, string $request = ''): array
    {
        $inquiry = curl_init($this->startPath . '/api/v1/transactions/?' . $request);
        curl_setopt($inquiry, CURLOPT_HTTPGET, 1);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user->getApiToken()
        ]);
        $response = curl_exec($inquiry);
        // Ошибка с биллинга
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен. 
            Попробуйте позднее');
        }
        curl_close($inquiry);

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingUnavailableException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array<App\Model\TransactionDto>', 'json');
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getCourse(string $courseCode): CourseDto
    {
        $inquiry = curl_init($this->startPath . '/api/v1/courses/' . $courseCode);
        curl_setopt($inquiry, CURLOPT_HTTPGET, 1);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($inquiry);
        // Ошибка с биллинга
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен. 
            Попробуйте позднее');
        }
        curl_close($inquiry);

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code']) && $result['code'] === 404) {
            throw new BillingUnavailableException($result['message']);
        }

        return $this->serializer->deserialize($response, CourseDto::class, 'json');
    }

    public function paymentCourse(User $user, string $codeCourse): PayDto
    {
        $inquiry = curl_init($this->startPath . '/api/v1/courses/' . $codeCourse . '/pay');
        curl_setopt($inquiry, CURLOPT_POST, 1);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user->getApiToken()
        ]);
        $response = curl_exec($inquiry);
        // Ошибка с биллинга
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен. 
            Попробуйте позднее');
        }
        curl_close($inquiry);

        // Ответа от сервиса
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingUnavailableException($result['message']);
        }

        return $this->serializer->deserialize($response, PayDto::class, 'json');
    }

    public function newCourse(User $user, CourseDto $courseDto): array
    {
        $response = $this->serializer->serialize($courseDto, 'json');
        $inquiry = curl_init($this->startPath . '/api/v1/courses/new');
        curl_setopt($inquiry, CURLOPT_POST, 1);
        curl_setopt($inquiry, CURLOPT_POSTFIELDS, $response);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user->getApiToken(),
            'Content-Length: ' . strlen($response)
        ]);
        $response = curl_exec($inquiry);
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен.');
        }
        curl_close($inquiry);

        $result = json_decode($response, true);
        if (isset($result['code']) && $result['code'] !== 201) {
            throw new BillingUnavailableException($result['message']);
        }

        return $result;
    }

    public function editCourse(User $user, string $codeCourse, CourseDto $courseDto): array
    {
        $response = $this->serializer->serialize($courseDto, 'json');
        $inquiry = curl_init($this->startPath . '/api/v1/courses/' . $codeCourse . '/edit');
        curl_setopt($inquiry, CURLOPT_POST, 1);
        curl_setopt($inquiry, CURLOPT_POSTFIELDS, $response);
        curl_setopt($inquiry, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($inquiry, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user->getApiToken(),
            'Content-Length: ' . strlen($response)
        ]);
        $response = curl_exec($inquiry);
        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен.');
        }
        curl_close($inquiry);

        $result = json_decode($response, true);
        if (isset($result['code']) && $result['code'] !== 200) {
            throw new BillingUnavailableException($result['message']);
        }

        return $result;
    }
}
