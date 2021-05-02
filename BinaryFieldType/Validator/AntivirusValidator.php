<?php

namespace Masilia\ClamavBundle\BinaryFieldType\Validator;

use eZ\Publish\Core\FieldType\BinaryFile\Value;
use eZ\Publish\Core\FieldType\Validator;
use eZ\Publish\Core\FieldType\ValidationError;
use eZ\Publish\Core\FieldType\Value as BaseValue;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Masilia\ClamavBundle\Services\AntiVirusInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Xenolope\Quahog\Client;

class AntivirusValidator extends Validator
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
     * @var bool[]
     */
    protected $constraints = [
        'antivirus' => false,
    ];
    /**
     * @var array[]
     */
    protected $constraintsSchema = [
        'antivirus' => [
            'type' => 'bool',
            'default' => false,
        ],
    ];

    /**
     * AntivirusValidator constructor.
     * @param AntiVirusInterface $antiVirus
     * @param KernelInterface $kernel
     * @param TranslatorInterface $translator
     * @param bool $isEnabled
     */
    public function __construct(
        AntiVirusInterface $antiVirus,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        bool $isEnabled
    ) {
        $this->constraints['antivirus'] = $isEnabled;
        $this->translator = $translator;
        $this->logger = new Logger('antivirus-scan');
        try {
            $this->logger->pushHandler(new StreamHandler(
                rtrim(
                    $kernel->getLogDir(),
                    '/'
                ) . '/antivirus-check.log'
            ));
        } catch (\Exception $e) {
        }
        $this->antiVirus = $antiVirus;
    }

    /**
     * @param mixed $constraints
     *
     * @return array
     */
    public function validateConstraints($constraints): array
    {
        $validationErrors = [];
        foreach ($constraints as $name => $value) {
            switch ($name) {
                case 'antivirus':
                    if ($value !== false && !is_bool($value)) {
                        $validationErrors[] = new ValidationError(
                            "Validator parameter '%parameter%' value must be of boolean type",
                            null,
                            [
                                '%parameter%' => $name,
                            ]
                        );
                    }
                    break;
                default:
                    $validationErrors[] = new ValidationError(
                        "Validator parameter '%parameter%' is unknown",
                        null,
                        [
                            '%parameter%' => $name,
                        ]
                    );
            }
        }

        return $validationErrors;
    }

    /**
     * Checks if $value->path is secured.
     *
     * @param Value $value
     *
     * @return bool
     */
    public function validate(BaseValue $value): bool
    {
        $isValid = true;
        if (null === $value) {
            return false;
        }
        $scanResult = $this->antiVirus->scan($value->path);

        $scanResult['originalFilename'] = $value->fileName;
        $scanResult['status'] = $scanResult['status'] ?? 'KO';

        if ($this->constraints['antivirus'] !== false && Client::RESULT_OK !== $scanResult['status']) {
            $this->errors[] = new ValidationError(
                $this->translator->trans('antivirus_constraint_message')
            );
            $isValid = false;
            $this->logger->warn(var_export($scanResult, true));
        } else {
            $this->logger->info(var_export($scanResult, true));
        }

        return $isValid;
    }
}