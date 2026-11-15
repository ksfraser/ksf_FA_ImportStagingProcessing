<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Http\Actions;

use ksfraser\FrontAccounting\ImportStaging\Http\ImportStagingRouter;

/**
 * Staging action — manages staged customers, transactions, payments.
 *
 * Handles sub-actions via $_REQUEST['sub_action'] or defaults to listing.
 *
 * @since 2.1.0
 * @BABOK Related: FR-ISU-009-001 staging management
 * @BABOK Related: FR-ISU-009-002 staged entity listing
 */
class StagingAction extends BaseAction
{
    public function run(string $action, array $request): void
    {
        $subAction = $request['sub_action'] ?? 'list';

        echo '<div class="staging-management">';
        echo '<h2>' . _('Staging Management') . '</h2>';

        switch ($subAction) {
            case 'customers':
                $this->renderCustomers($request);
                break;
            case 'transactions':
                $this->renderTransactions($request);
                break;
            case 'payments':
                $this->renderPayments($request);
                break;
            case 'audit':
                $this->renderAudit($request);
                break;
            case 'list':
            default:
                $this->renderDashboard($request);
                break;
        }

        echo '</div>';
    }

    private function renderDashboard(array $request): void
    {
        echo '<p>' . _('Select an entity type to manage:') . '</p>';
        echo '<ul>';
        echo '<li><a href="?action=staging&amp;sub_action=customers">' . _('Staged Customers') . '</a></li>';
        echo '<li><a href="?action=staging&amp;sub_action=transactions">' . _('Staged Transactions') . '</a></li>';
        echo '<li><a href="?action=staging&amp;sub_action=payments">' . _('Staged Payments') . '</a></li>';
        echo '<li><a href="?action=staging&amp;sub_action=audit">' . _('Processing Audit') . '</a></li>';
        echo '</ul>';
    }

    private function renderCustomers(array $request): void
    {
        echo '<h3>' . _('Staged Customers') . '</h3>';
        echo '<p>' . _('Customer staging records will appear here.') . '</p>';
        echo '<table class="tablestyle" width="100%">';
        echo '<tr><th>' . _('ID') . '</th><th>' . _('Name') . '</th><th>' . _('Source') . '</th><th>' . _('Status') . '</th></tr>';
        echo '<tr><td colspan="4">' . _('No staged customers found.') . '</td></tr>';
        echo '</table>';
    }

    private function renderTransactions(array $request): void
    {
        echo '<h3>' . _('Staged Transactions') . '</h3>';
        echo '<p>' . _('Transaction staging records will appear here.') . '</p>';
        echo '<table class="tablestyle" width="100%">';
        echo '<tr><th>' . _('ID') . '</th><th>' . _('Reference') . '</th><th>' . _('Amount') . '</th><th>' . _('Status') . '</th></tr>';
        echo '<tr><td colspan="4">' . _('No staged transactions found.') . '</td></tr>';
        echo '</table>';
    }

    private function renderPayments(array $request): void
    {
        echo '<h3>' . _('Staged Payments') . '</h3>';
        echo '<p>' . _('Payment staging records will appear here.') . '</p>';
        echo '<table class="tablestyle" width="100%">';
        echo '<tr><th>' . _('ID') . '</th><th>' . _('Reference') . '</th><th>' . _('Amount') . '</th><th>' . _('Status') . '</th></tr>';
        echo '<tr><td colspan="4">' . _('No staged payments found.') . '</td></tr>';
        echo '</table>';
    }

    private function renderAudit(array $request): void
    {
        echo '<h3>' . _('Processing Audit') . '</h3>';
        echo '<p>' . _('Audit trail entries will appear here.') . '</p>';
    }
}
