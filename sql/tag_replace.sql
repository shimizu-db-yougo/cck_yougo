DELIMITER //

DROP FUNCTION IF EXISTS `REPLACE_TAGS`//
CREATE FUNCTION REPLACE_TAGS( x longtext CHARACTER SET utf8 COLLATE utf8_general_ci) RETURNS longtext  CHARACTER SET utf8 COLLATE utf8_general_ci
LANGUAGE SQL NOT DETERMINISTIC READS SQL DATA
BEGIN
	IF x IS NOT NULL THEN
		SET x = REPLACE(x,'《rtn》','');
		SET x = REPLACE(x,'《c_KJ》','');
		SET x = REPLACE(x,'《/c_KJ》','');
		SET x = REPLACE(x,'《c_GAI》','');
		SET x = REPLACE(x,'《/c_GAI》','');
		SET x = REPLACE(x,'《c_UT》','');
		SET x = REPLACE(x,'《/c_UT》','');
		SET x = REPLACE(x,'《c_ST》','');
		SET x = REPLACE(x,'《/c_ST》','');
	END IF;
	
	RETURN x;
END;
//
