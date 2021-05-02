<?php

namespace Masilia\ClamavBundle\Constraints;

use Symfony\Component\Validator\Constraint;

class Antivirus extends Constraint
{
    /**
     * @var string
     */
    public $message = 'antivirus_constraint_message';

    /**
     * Antivirus constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        parent::__construct();
    }

}