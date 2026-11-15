<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Http\Actions;

use ksfraser\FrontAccounting\ImportStaging\Http\ImportStagingRouter;

/**
 * Matching action — matches staged transactions against FA records.
 *
 * @since 2.1.0
 * @BABOK Related: FR-ISU-009-001 matching
 */
class MatchingAction extends BaseAction
{
    public function run(string $action, array $request): void
    {
        echo '<div class="matching-view">';
        echo '<h2>' . _('Transaction Matching') . '</h2>';
        echo '<p>' . _('Match staged transactions against FA invoices and payments.') . '</p>';
        echo '<table class="tablestyle" width="100%">';
        echo '<tr><th>' . _('Staged Ref') . '</th><th>' . _('FA Ref') . '</th><th>' . _('Amount') . '</th><th>' . _('Status') . '</th></tr>';
        echo '<tr><td colspan="4">' . _('No matches pending.') . '</td></tr>';
        echo '</table>';
        echo '</div>';
    }
}
