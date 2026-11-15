<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Http\Actions;

use ksfraser\FrontAccounting\ImportStaging\Http\ImportStagingRouter;

/**
 * Config action — module configuration and field mapping.
 *
 * @since 2.1.0
 * @BABOK Related: FR-ISU-015-002 configuration view
 */
class ConfigAction extends BaseAction
{
    public function run(string $action, array $request): void
    {
        $subAction = $request['sub_action'] ?? 'config';

        echo '<div class="isu-config">';
        echo '<h2>' . _('ISU Configuration') . '</h2>';

        switch ($subAction) {
            case 'mapping':
                $this->renderMapping($request);
                break;
            case 'config':
            default:
                $this->renderConfig($request);
                break;
        }

        echo '</div>';
    }

    private function renderConfig(array $request): void
    {
        echo '<p>' . _('ISU configuration settings.') . '</p>';
        echo '<form method="post" action="?action=config">';
        echo '<table class="tablestyle" width="100%">';
        echo '<tr><th colspan="2">' . _('Staging Settings') . '</th></tr>';
        echo '<tr><td>' . _('Default Customer') . '</td><td><input type="text" name="default_customer" value=""></td></tr>';
        echo '<tr><td>' . _('Days Before') . '</td><td><input type="text" name="days_before" value="2"></td></tr>';
        echo '<tr><td>' . _('Days After') . '</td><td><input type="text" name="days_after" value="5"></td></tr>';
        echo '<tr><td colspan="2"><input type="submit" value="' . _('Save Configuration') . '"></td></tr>';
        echo '</table>';
        echo '</form>';
    }

    private function renderMapping(array $request): void
    {
        echo '<h3>' . _('Field Mapping') . '</h3>';
        echo '<p>' . _('Field mapping configuration will appear here.') . '</p>';
    }
}
