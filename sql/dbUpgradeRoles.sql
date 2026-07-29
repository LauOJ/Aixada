-- Role migration for La Vinagreta
-- Run once on each environment (proves, PRD)
-- Renames existing roles to new names and removes obsolete ones.

-- Rename to new role names
UPDATE aixada_user_role SET role = 'consumidora'  WHERE role IN ('Consumer', 'Consumidora');
UPDATE aixada_user_role SET role = 'responsable'  WHERE role IN ('Comissió de consum');
UPDATE aixada_user_role SET role = 'tresoreria'   WHERE role IN ('Comissió econo-legal', 'Accounts Commission', 'Econo-Legal Commission');
UPDATE aixada_user_role SET role = 'admin'        WHERE role IN ('Comissió d\'informàtica', 'Hacker Commission');

-- Remove obsolete roles (no equivalent in new system)
DELETE FROM aixada_user_role WHERE role IN (
    'Caixa',
    'La cinquena',
    'Comissió de logística',
    'Productor',
    'Logistic Commission',
    'Fifth Column Commission',
    'Producer',
    'Checkout',
    'Consumer Commission'
);

-- Give all users both consumidora and responsable as baseline
INSERT IGNORE INTO aixada_user_role (user_id, role)
SELECT id, 'consumidora' FROM aixada_user;

INSERT IGNORE INTO aixada_user_role (user_id, role)
SELECT id, 'responsable' FROM aixada_user;

-- Note: 'torns' role does not exist yet — assign manually via activate_roles after running this script.
