<?php
/**
 * Staging Imports Admin Page — links/actions for Import Staging module.
 *
 * Provides the admin UI entry point for staging management (FR-ISU-015-002).
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-015-002 staging management UI
 * @BABOK Related: FR-ISU-009-001 matching
 * @BABOK Related: FR-ISU-012-001 processing pipeline
 * @BABOK Related: NFR-ISU-004 page security
 */
// Staging Imports Admin Page — links/actions for Import Staging module
// Hooks: stage_customer_data (Square), stage_refund (Square), stage_inventory_adjustment (Square), stage_payment_type (Square)
// Services: MatchingService, ProcessingPipeline, StagingService
// BABOK: FR-01 Import Processing, FR-02 Matching, FR-03 Pipeline
