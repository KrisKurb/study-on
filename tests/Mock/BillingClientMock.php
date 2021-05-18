<?php


namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Model\CourseDto;
use App\Model\PayDto;
use App\Model\TransactionDto;
use App\Model\UserDto;
use App\Security\User;
use App\Service\BillingClient;
use App\Service\DecodingJwt;
use DateInterval;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BillingClientMock extends BillingClient
{
    /** @var UserDto */
    private $userDefault;

    /** @var UserDto */
    private $userSuperAdmin;

    /** @var CourseDto[]  */
    public $courses;
    private $typesCourse;

    /** @var TransactionDto[]  */
    public $transactions;
    private $typesTransaction;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);

        // Обычный пользователь
        $this->userDefault = new UserDto();
        $this->userDefault->setUsername('user@mail.ru');
        $this->userDefault->setPassword('123456');
        $this->userDefault->setRoles(["ROLE_USER"]);
        $this->userDefault->setBalance(5000);

        // Супер админ пользователь
        $this->userSuperAdmin = new UserDto();
        $this->userSuperAdmin->setUsername('admin@mail.ru');
        $this->userSuperAdmin->setPassword('123456');
        $this->userSuperAdmin->setRoles(["ROLE_SUPER_ADMIN"]);
        $this->userSuperAdmin->setBalance(0);

        // Курсы
        $this->typesCourse = [
            1 => 'rent',
            2 => 'free',
            3 => 'buy',
        ];

        $dataCourse = [
            // Для аренды
            [
                'code' => 'LFGFDGJFDJJCHJVHJF5',
                'title' => 'Курс испанского языка (1 уровень)',
                'type' => $this->typesCourse[1],
                'price' => 999,
            ],
            [
                'code' => 'LFGFDGJFDJJCHJV4514',
                'title' => 'Курс испанского языка (2 уровень)',
                'type' => $this->typesCourse[1],
                'price' => 1100,
            ],
            [
                'code' => 'LFGFDGJFDJJCHJV4511',
                'title' => 'Курс испанского языка (3 уровень)',
                'type' => $this->typesCourse[1],
                'price' => 2000,
            ],
            // Бесплатные курсы
            [
                'code' => 'JVJNJKBNDNJFDDFF444',
                'title' => 'Курс корейского языка (1 уровень)',
                'type' => $this->typesCourse[2],
                'price' => 0,
            ],
            [
                'code' => 'JVJNJKBNDNJFDDFF445',
                'title' => 'Курс корейского языка (2 уровень)',
                'type' => $this->typesCourse[2],
                'price' => 0
            ],
            [
                'code' => 'JVJNJKBNDNJFDDFF447',
                'title' => 'Курс корейского языка (3 уровень)',
                'type' => $this->typesCourse[2],
                'price' => 0
            ],
            // Разовая покупка
            [
                'code' => 'DSFDFSDFDSLFLHGLHLG',
                'title' => 'Курс английского языка (1 уровень)',
                'type' => $this->typesCourse[3],
                'price' => 1500,
            ],
            [
                'code' => 'DSFDJGMFKGJMDLKLDDD',
                'title' => 'Курс английского языка (2 уровень)',
                'type' => $this->typesCourse[3],
                'price' => 2000,
            ],
            [
                'code' => 'DSFDFSDFDFDFFSDFSDG',
                'title' => 'Курс английского языка (3 уровень)',
                'type' => $this->typesCourse[3],
                'price' => 2400,
            ],
        ];
        $json = $this->serializer->serialize($dataCourse, 'json');
        $this->courses = $this->serializer->deserialize($json, 'array<App\Model\CourseDto>', 'json');

        // Транзакции
        $this->typesTransaction = [
            1 => 'payment',
            2 => 'deposit',
        ];

        $transactionDeposit = new TransactionDto();
        $transactionDeposit->setId(1);
        $transactionDeposit->setAmount(5000);
        $transactionDeposit->setCreatedAt('2021-04-03 00:00:00');
        $transactionDeposit->setType($this->typesTransaction[2]);

        $transactionPayment = new TransactionDto();
        $transactionPayment->setId(2);
        $transactionPayment->setAmount($this->courses[8]->getPrice());
        $transactionPayment->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $transactionPayment->setType($this->typesTransaction[1]);
        $transactionPayment->setCourseCode($this->courses[8]->getCode());

        $this->transactions = [
            $transactionDeposit,
            $transactionPayment,
        ];

        $this->userDefault->setBalance($this->userDefault->getBalance() - $transactionPayment->getAmount());
    }

    public function auth(string $request): UserDto
    {
        /** @var UserDto $userDto  */
        $userDto =$this->serializer->deserialize($request, UserDto::class, 'json');
        if ($userDto->getUsername() === $this->userDefault->getUsername() &&
            $userDto->getPassword() === $this->userDefault->getPassword()) {
            $userDto->setToken($this->generateToken('ROLE_USER', $this->userDefault->getUsername()));
            $userDto->setRoles(["ROLE_USER"]);
            $userDto->setRefreshToken('454cbc');
            return $userDto;
        }
        if ($userDto->getUsername() === $this->userSuperAdmin->getUsername() &&
            $userDto->getPassword() === $this->userSuperAdmin->getPassword()) {
            $userDto->setToken($this->generateToken('ROLE_SUPER_ADMIN', $this->userSuperAdmin->getUsername()));
            $userDto->setRoles(["ROLE_SUPER_ADMIN", "ROLE_USER"]);
            $userDto->setRefreshToken('454cbc');
            return $userDto;
        }
        throw new BillingUnavailableException('Проверьте правильность введёного логина и пароля');
    }

    public function register(UserDto $dataUser): UserDto
    {
        // Симуляция обработки уже существующих пользователей
        if ($dataUser->getUsername() === $this->userDefault->getUsername()|
            $dataUser->getUsername() === $this->userSuperAdmin->getUsername()) {
            throw new \Exception('Данный пользователь уже существует');
        }
        $token = $this->generateToken('ROLE_USER', $dataUser->getUsername());
        $dataUser->setToken($token);
        $dataUser->setBalance(0);
        $dataUser->setRoles(["ROLE_USER"]);
        $dataUser->setRefreshToken('454cb1');
        return $dataUser;
    }

    private function generateToken(string $role, string $username): string
    {
        $roles = null;
        if ($role === 'ROLE_USER') {
            $roles = ["ROLE_USER"];
        } elseif ($role === 'ROLE_SUPER_ADMIN') {
            $roles = ["ROLE_SUPER_ADMIN", "ROLE_USER"];
        }
        $data = [
            'username' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));
        return 'header.' . $query . '.signature';
    }

    public function getCurrentUser(User $user, DecodingJwt $decodingJwt)
    {
        $decodingJwt->decoding($user->getApiToken());
        if ($decodingJwt->getUsername() === $this->userDefault->getUsername()) {
            $data = [
                'username' => $decodingJwt->getUsername() ,
                'roles' => $decodingJwt->getRoles(),
                'balance' => $this->userDefault->getBalance(),
            ];
            return $this->serializer->serialize($data, 'json');
        }
        if ($decodingJwt->getUsername() === $this->userSuperAdmin->getUsername()) {
            $data = [
                'username' => $decodingJwt->getUsername() ,
                'roles' => $decodingJwt->getRoles(),
                'balance' => $this->userSuperAdmin->getBalance(),
            ];
            return $this->serializer->serialize($data, 'json');
        }

        $data = [
            'username' => $decodingJwt->getUsername() ,
            'roles' => $decodingJwt->getRoles(),
            'balance' => 0,
        ];
        return $this->serializer->serialize($data, 'json');
    }

    public function getAllCourses(): array
    {
        return $this->courses;
    }

    public function transactions(User $user, string $request = ''): array
    {
        if ($request === '') {
            // декодируем токен
            $decodingJwt = new  DecodingJwt();
            $decodingJwt->decoding($user->getApiToken());

            if ($decodingJwt->getUsername() === $this->userDefault->getUsername()) {
                return $this->transactions;
            }

            if ($decodingJwt->getUsername() === $this->userSuperAdmin->getUsername()) {
                return [];
            }
        }

        $filters = explode('&', $request);

        $typesRequest = [];
        $valuesRequest= [];

        foreach ($filters as $filter) {
            $temp = explode('=', $filter);
            $typesRequest[] = $temp[0];
            $valuesRequest[$temp[0]] = $temp[1];
        }

        $responseTransactions = [];

        if (in_array('skip_expired', $typesRequest, true)
            && in_array('type', $typesRequest, true)
            && in_array('course_code', $typesRequest, true)
        ) {
            foreach ($this->transactions as $transaction) {
                if ($valuesRequest['type'] === $transaction->getType()
                    && $valuesRequest['course_code'] === $transaction->getCourseCode()
                    && (
                        (
                            $transaction->getExpiresAt() !== null &&
                            $transaction->getExpiresAt()> (new \DateTime())->format('Y-m-d H:i:s')
                        ) |
                        (true)
                    )
                ) {
                    $responseTransactions[] = $transaction;
                }
            }
            return $responseTransactions;
        }

        if (in_array('skip_expired', $typesRequest, true)
            && in_array('type', $typesRequest, true)
        ) {
            foreach ($this->transactions as $transaction) {
                if ($valuesRequest['type'] === $transaction->getType()
                    && (
                        (
                            $transaction->getExpiresAt() !== null &&
                            $transaction->getExpiresAt()> (new \DateTime())->format('Y-m-d H:i:s')
                        ) |
                        (true)
                    )
                ) {
                    $responseTransactions[] = $transaction;
                }
            }
            return $responseTransactions;
        }

        throw new AccessDeniedException();
    }

    public function getCourse(string $courseCode): CourseDto
    {
        // Ищем код курса на сервере
        $index = null;
        foreach ($this->courses as $key => $course) {
            if ($course->getCode() === $courseCode) {
                $index = $key;
            }
        }

        if ($index === null) {
            throw new BillingUnavailableException('Данный курс не найден');
        }

        return $this->courses[$index];
    }

    public function newCourse(User $user, CourseDto $courseDto): array
    {
        if ($user->getRoles()[0] !== 'ROLE_SUPER_ADMIN') {
            throw new AccessDeniedException();
        }

        foreach ($this->courses as $key => $course) {
            if ($course->getCode() === $courseDto->getCode()) {
                throw new BillingUnavailableException('Данный курс уже существует в системе', 405);
            }
        }

        $this->courses[] = $courseDto;

        return [
            'code' => 201,
            'success' => true,
        ];
    }

    public function editCourse(User $user, string $codeCourse, CourseDto $courseDto): array
    {
        if ($user->getRoles()[0] !== 'ROLE_SUPER_ADMIN') {
            throw new AccessDeniedException();
        }

        $flag = false;
        foreach ($this->courses as $key => $course) {
            if ($course->getCode() === $codeCourse) {
                $this->courses[$key]  = $courseDto;
                $flag = true;
            }
        }
        if (!$flag) {
            throw new BillingUnavailableException('Данный курс в системе не найден', 404);
        }

        $this->courses[] = $courseDto;
        return [
            'code' => 200,
            'success' => true,
        ];
    }

    public function paymentCourse(User $user, string $codeCourse): PayDto
    {
        if (!$user) {
            throw new AccessDeniedException();
        }

        $flag = false;
        foreach ($this->courses as $course) {
            if ($course->getCode() === $codeCourse) {
                $courseDto = $course;
                $flag = true;
            }
        }
        if (!$flag) {
            throw new BillingUnavailableException('Данный курс в системе не найден', 404);
        }
        $transaction = new TransactionDto();
        $transaction->setCourseCode($codeCourse);
        $transaction->setType($courseDto->getType());
        $transaction->setAmount($courseDto->getPrice());
        $transaction->setId(count($this->transactions)-1);
        $transaction->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        if ($courseDto->getType() === 'rent') {
            $transaction->setExpiresAt(((new \DateTime())->add(new DateInterval('P1W')))->format('Y-m-d H:i:s'));
        }
        $this->transactions[] = $transaction;

        $payDto = [
            'success' => true,
            'course_type' => $courseDto->getType(),
            'expires_at' =>$transaction->getExpiresAt() ? $transaction->getExpiresAt() : null,
        ];
        $json = $this->serializer->serialize($payDto, 'json');
        return $this->serializer->deserialize($json, PayDto::class, 'json');
    }
}
