<?php

namespace Masilia\ClamavBundle\Constraints;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Masilia\ClamavBundle\Services\AntiVirusInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Xenolope\Quahog\Client;

class AntivirusValidator extends ConstraintValidator
{
    /** @var TranslatorInterface */
    protected $translator;
    /**
     * @var AntiVirusInterface
     */
    protected $antiVirus;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * AntivirusValidator constructor.
     * @param AntiVirusInterface $antiVirus
     * @param TranslatorInterface $translator
     * @param KernelInterface $kernel
     */
    public function __construct(
        AntiVirusInterface $antiVirus,
        TranslatorInterface $translator,
        KernelInterface $kernel
    ) {
        $this->translator = $translator;
        $this->logger = new Logger('antivirus-scan');
        $this->logger->pushHandler(new StreamHandler(
            rtrim(
                $kernel->getLogDir(),
                '/'
            ).'/antivirus-check.log'
        ));
        $this->antiVirus = $antiVirus;
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     *
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Antivirus) {
            throw new UnexpectedTypeException($constraint, AntiVirus::class);
        }
        if (null === $value || '' === $value) {
            return;
        }
        $scanResult = (array) $this->antiVirus->scan($value);

        if ($value instanceof File) {
            $scanResult['originalFilename'] = $value->getClientOriginalName();
        }
        $scanResult['status'] = $scanResult['status'] ?? 'KO';

        if (Client::RESULT_OK === $scanResult['status']) {
            $this->logger->info(var_export($scanResult, true));
        } else {
            $scanResult['reason'] = $scanResult['reason'] ?? '';
            $this->logger->warn(var_export($scanResult, true));
            $this->context->buildViolation($this->translator->trans($constraint->message))
                ->setParameter('{{reason}}', $scanResult['reason'])
                ->addViolation();
        }
    }
}
