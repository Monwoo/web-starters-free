<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Form;

use MWS\MoonManagerBundle\Repository\MwsUserRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MwsUserAdminType extends MwsUserType
{
    public function __construct(
        protected Security $security,
        protected MwsUserRepository $mwsUserRepository,
    ){
        parent::__construct($security, $mwsUserRepository);
    }
}
