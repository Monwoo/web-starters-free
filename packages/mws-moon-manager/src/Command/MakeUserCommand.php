<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsUser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'mws:add-user',
    description: 'Add user for this CRM.',
    hidden: false,
    // aliases: ['app:make-user']
)]
class MakeUserCommand extends Command
{
    public function __construct(
        protected ManagerRegistry $registry,
        protected EntityManagerInterface $em,
        protected UserPasswordHasherInterface $passwordHasher,
        protected string $userLogin = 'e2e-user@test.localhost',
        protected bool $cleanAllUsers = false,
    ) {
        // TIPS : best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        // $this->requirePassword = $requirePassword;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'userLogin', '-l', InputArgument::OPTIONAL,
            "Login de l'utilisateur", $this->userLogin
        );
        $this->addOption(
            'cleanAllUsers', '-c', InputArgument::OPTIONAL,
            "Clean de tous les utilisateurs", $this->cleanAllUsers
        );
    }

    protected function addUser($output, $config) {
        $pass = $config['password'];
        $connection = $this->registry->getConnection();
        $hashedPassword = $this->passwordHasher->hashPassword(
            new MwsUser(),
            $pass
        );

        $userData = $config['data'];
        $userData["password"] = $hashedPassword;

        $resp = $connection->insert('mws_user', $userData);
        // $connection->prepare('COMMIT;')->execute();
        // $connection->prepare()->execute();
        // https://stackoverflow.com/questions/8707486/doctrine2-how-to-improve-flush-efficiency
        // $em->flush();
        // $em->clear();
        // dd($resp);
        $output->writeln(
            "[" . get_class($connection) . "] New MWS user inserted OK."
        );
        $output->writeln([
            "<info>Did save MWS USER as : {$this->userLogin}</>",
        ]);
        $output->writeln([
            "<info>With password : {$pass}</>",
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->userLogin = $input->getOption("userLogin");
        $this->cleanAllUsers = $input->getOption("cleanAllUsers");

        $cmdStatus = Command::SUCCESS;

        if ($this->cleanAllUsers) {
            $qb = $this->em->createQueryBuilder()
            // ->delete('MoonManagerBundle:MwsUser', 'u'); // Depreciated syntax in 6.3 ? sound to work...         
            ->delete(MwsUser::class, 'u');               
            // ->where('u.xx = :xx')
            // ->setParameter('xx', $xx)

            // ->select('u')                
            // ->from('App:MwsUser', 'u')                
            // ->where('u.xx = :xx')
            // ->setParameter('xx', $xx);

            $query = $qb->getQuery();
            dump($query->getSql());

            $resp = $query->execute();
            // dd($resp);
            // foreach ($resp as $u) {
            //     // dd($u);
            //     $u->xx($xx);
            // }

            $this->em->flush();    

            $output->writeln([
                "<info>Did remove all users</>",
            ]);
        }
 
        $config = [
            'password' => "password", // TODO : read password from input... ?
            'data' => [
                "username" => $this->userLogin,
                // "lastname" => $this->userLogin,
                // "firstname" => $this->userLogin,
                // "created_at" => date("Y-m-d H:i:s"),
                "roles" => json_encode([  ]),
            ],
        ];
        $this->addUser($output, $config);

        return $cmdStatus;
    }
}
