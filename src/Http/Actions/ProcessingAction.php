<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Http\Actions;

use ksfraser\FrontAccounting\ImportStaging\Http\ImportStagingRouter;

/**
 * Processing action — processes staged records into FA.
 *
 * @since 2.1.0
 * @BABOK Related: FR-ISU-012-001 processing pipeline
 */
class ProcessingAction extends BaseAction
{
    public function run(string $action, array $request): void
    {
        echo '<div class="processing-view">';
        echo '<h2>' . _('Processing Pipeline') . '</h2>';
        echo '<p>' . _('Process staged records into FrontAccounting.') . '</p>';
        echo '<table class="tablestyle" width="100%">';
        echo '<tr><th>' . _('Stage') . '</th><th>' . _('Status') . '</th><th>' . _('Records') . '</th></tr>';
        echo '<tr><td>' . _('Customer Staging') . '</td><td>' . _('Idle') . '</td><td>0</td></tr>';
        echo '<tr><td>' . _('Transaction Staging') . '</td><td>' . _('Idle') . '</td><td>0</td></tr>';
        echo '<tr><td>' . _('Payment Staging') . '</td><td>' . _('Idle') . '</td><td>0</td></tr>';
        echo '</table>';
        echo '</div>';
    }
}
