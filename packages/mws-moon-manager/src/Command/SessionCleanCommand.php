<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Command;

use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
// use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

#[AsCommand(
    name: 'mws:session:clean',
    description: 'Will do native session cleanings.',
    hidden: false,
    // aliases: ['backup:sync']
)]
class SessionCleanCommand extends Command
{
    private Filesystem $filesystem;

    public function __construct(
        protected ParameterBagInterface $params,
        // protected SessionInterface $session,
    ) {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        // $this->addOption(
        //     'userLogin', '-l', InputArgument::OPTIONAL,
        //     "Login de l'utilisateur", $this->userLogin
        // );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $cmdStatus = Command::SUCCESS;
        // https://www.appsloveworld.com/symfony/100/85/delete-or-clear-session-in-symfony-4-4
        // https://stackoverflow.com/questions/31312068/symfony2-session-and-cacheclear-command

        // TODO : cookies too ?
        // https://copyprogramming.com/howto/symfony-functional-test-clear-session-and-cookies
        // $this->get('session')->clear();
        // $this->session->clear();
        $session = new Session();
        $session->clear(); // BUT ONLY clear previous opened session...
        // https://stackoverflow.com/questions/19048320/symfony2-how-can-i-see-a-list-of-open-sessions
        $session_dir = ini_get('session.save_path'); // BUT command line php might not be the same as the server one...
        // $sessions = preg_grep('/^([^.])/', scandir($session_dir));
        // $logged_in_user_array = array();
        // $one_hour_ago = strtotime('-1 hour');
        // foreach ($sessions as $key => $session) {
        //     $session_file_contents = file_get_contents($session_dir . '/' . $session);
        //     $decoded_array = $this->unserialize_php($session_file_contents, '|');
        //     $updated =  $decoded_array['_sf2_meta']['u'];
        //     if (!is_null($decoded_array['_sf2_attributes']['_security_secured_area'])) {
        //         $decoded_array2 = self::unserialize_php($decoded_array['_sf2_attributes']['_security_secured_area'], '"');
        //         $keys = array_keys($decoded_array2);
        //         if ($one_hour_ago < $updated) $logged_in_user_array[$decoded_array2[$keys[4]][0] . ''] = str_replace(array('domain1\\', 'domain2\\'), '', $decoded_array2[$keys[4]][0] . '');
        //     }
        // }
        // var_dump($logged_in_user_array);

        // // https://symfony.com/doc/current/session.html#configuration
        // handler_id: 'session.handler.native_file'
        // save_path: '%kernel.project_dir%/var/sessions/%kernel.environment%'

        $output->writeln([
            "<info>TODO : dev : '$session_dir'</info>",
        ]);

        return $cmdStatus;
    }

    /**
     * https://stackoverflow.com/questions/19048320/symfony2-how-can-i-see-a-list-of-open-sessions
     * 
     * @param $session_data
     * @param $delimiter
     * @return array
     * @throws \Exception
     */
    private static function unserialize_php($session_data, $delimiter)
    {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), $delimiter)) {
                throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, $delimiter, $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}
