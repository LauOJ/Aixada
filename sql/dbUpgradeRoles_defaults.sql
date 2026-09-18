-- Default roles for new members — role migration follow-up for La Vinagreta
-- Run once on each environment (proves, then PRD), AFTER dbUpgradeRoles.sql.
--
-- Recreates new_user_member so that every member created from now on gets the
-- new default roles 'consumidora' + 'responsable' (it used to assign the old
-- 'Consumer' + 'Checkout'). Existing users are not affected by this script.

DROP PROCEDURE IF EXISTS new_user_member;

DELIMITER $$
CREATE PROCEDURE new_user_member(
        in the_login varchar(50),
        in the_password varchar(255),
        in the_uf_id int,
        in the_custom_ref varchar(100),
        in the_name varchar(255),
        in the_nif varchar(15),
        in the_address varchar(255),
        in the_city varchar(255),
        in the_zip varchar(10),
        in the_phone1 varchar(50),
        in the_phone2 varchar(50),
        in the_web varchar(255),
        in the_notes text,
        in the_active boolean,
        in the_participant boolean,
        in the_adult boolean,
        in the_language char(5),
        in the_gui_theme varchar(50),
        in the_email varchar(100)
        )
begin
  declare the_user_id int;
  start transaction;

  	select
  		max(id)+1
  	into
  		the_user_id
  	from
  		aixada_user
  	where
  		id<1000;


  	if the_user_id=0 or isnull(the_user_id) then set the_user_id=1; end if;

  	insert into
  		aixada_member (id, custom_member_ref, uf_id, nif, name, address, zip, city, phone1, phone2, web, notes, active, participant, adult)
  	values
  		(the_user_id, the_custom_ref, the_uf_id, the_nif, the_name, the_address, the_zip, the_city, the_phone1, the_phone2, the_web, the_notes, the_active, the_participant, the_adult);


  	insert into
  		aixada_user (id, login, password, email, uf_id, member_id, language, gui_theme, created_on)
  	values
 		(the_user_id, the_login, the_password, the_email, the_uf_id, the_user_id, the_language, the_gui_theme, sysdate());


  	insert into
  		aixada_user_role (user_id, role)
  	values
     ( the_user_id, 'consumidora' ),
     ( the_user_id, 'responsable');

	commit;
end$$
DELIMITER ;
