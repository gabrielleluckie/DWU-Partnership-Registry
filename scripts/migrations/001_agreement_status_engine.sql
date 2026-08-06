-- Agreement Status Engine migration
-- Adds Expiring Soon status support and expiry alert tracking.

ALTER TABLE agreement
    MODIFY COLUMN Status ENUM(
        'Draft',
        'Submitted',
        'Under Review',
        'Revision Required',
        'Approved',
        'Rejected',
        'Active',
        'Expiring Soon',
        'Expired'
    ) NOT NULL DEFAULT 'Active';

ALTER TABLE agreement
    ADD COLUMN IF NOT EXISTS Expiry_Alert_Sent_At DATETIME NULL DEFAULT NULL
        AFTER Expiry_Date;
