<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Contracts\CustomerRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\TransactionRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\LineItemRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentMatchRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\AuditLogRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\StagingManagerInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\MatchingServiceInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationServiceInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ProcessorInterface;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingLineItemDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentMatchDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingLogDAO;

/**
 * Verify that all repository interfaces exist and that ISU DAOs implement them.
 *
 * @BABOK Related: FR-Repository-Pattern
 */
class RepositoryInterfaceTest extends TestCase
{
    /**
     * @test
     * Repository interfaces exist and are loadable.
     *
     * @coversNothing
     */
    public function allRepositoryInterfacesExist(): void
    {
        $interfaces = [
            CustomerRepositoryInterface::class,
            TransactionRepositoryInterface::class,
            PaymentRepositoryInterface::class,
            LineItemRepositoryInterface::class,
            PaymentMatchRepositoryInterface::class,
            AuditLogRepositoryInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $this->assertTrue(
                interface_exists($interface),
                "Interface {$interface} must exist"
            );
        }
    }

    /**
     * @test
     * ISU DAOs implement the corresponding repository interfaces.
     *
     * @coversNothing
     */
    public function isuDaosImplementRepositoryInterfaces(): void
    {
        $db = new \ksf_ModulesDAO();
        $prefix = '0_test_';

        $this->assertInstanceOf(
            CustomerRepositoryInterface::class,
            new StagingCustomerDAO($prefix, $db)
        );
        $this->assertInstanceOf(
            TransactionRepositoryInterface::class,
            new StagingTransactionDAO($prefix, $db)
        );
        $this->assertInstanceOf(
            PaymentRepositoryInterface::class,
            new StagingPaymentDAO($prefix, $db)
        );
        $this->assertInstanceOf(
            LineItemRepositoryInterface::class,
            new StagingLineItemDAO($prefix, $db)
        );
        $this->assertInstanceOf(
            PaymentMatchRepositoryInterface::class,
            new StagingPaymentMatchDAO($prefix, $db)
        );
        $this->assertInstanceOf(
            AuditLogRepositoryInterface::class,
            new StagingLogDAO($prefix, $db)
        );
    }

    /**
     * @test
     * Existing service contracts are loadable.
     *
     * @coversNothing
     */
    public function serviceContractsExist(): void
    {
        $this->assertTrue(interface_exists(StagingManagerInterface::class));
        $this->assertTrue(interface_exists(MatchingServiceInterface::class));
        $this->assertTrue(interface_exists(ValidationServiceInterface::class));
        $this->assertTrue(interface_exists(ProcessorInterface::class));
    }
}
