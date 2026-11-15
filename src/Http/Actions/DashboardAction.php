<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Http\Actions;

use ksfraser\FrontAccounting\ImportStaging\Http\ImportStagingRouter;

/**
 * Dashboard action — default landing view for Import Staging.
 *
 * Displays staging status counts and quick links to staging management.
 *
 * @since 2.1.0
 * @BABOK Related: FR-ISU-015-001 dashboard view
 */
class DashboardAction extends BaseAction
{
    public function run(string $action, array $request): void
    {
        echo '<div class="dashboard">';
        echo '<h2>' . _('Import Staging Dashboard') . '</h2>';

        // Status summary
        echo '<table class="tablestyle" width="100%">';
        echo '<tr><th colspan="3">' . _('Staging Status Summary') . '</th></tr>';
        echo '<tr><th>' . _('Entity Type') . '</th><th>' . _('Pending') . '</th><th>' . _('Processed') . '</th></tr>';

        $entities = ['customers' => _('Customers'), 'transactions' => _('Transactions'), 'payments' => _('Payments')];
        foreach ($entities as $key => $label) {
            echo '<tr>';
            echo '<td>' . $label . '</td>';
            echo '<td>-</td>';
            echo '<td>-</td>';
            echo '</tr>';
        }
        echo '</table>';

        echo '<p>' . _('Use the left menu to manage staging records.') . '</p>';
        echo '</div>';
    }
}
