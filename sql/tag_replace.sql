DELIMITER //

DROP FUNCTION IF EXISTS `REPLACE_TAGS`//
CREATE FUNCTION REPLACE_TAGS( x longtext CHARACTER SET utf8 COLLATE utf8_general_ci) RETURNS longtext  CHARACTER SET utf8 COLLATE utf8_general_ci
LANGUAGE SQL NOT DETERMINISTIC READS SQL DATA
BEGIN
	DECLARE wk_before longtext CHARACTER SET utf8 COLLATE utf8_general_ci;
	DECLARE wk_after longtext CHARACTER SET utf8 COLLATE utf8_general_ci;

	IF x IS NOT NULL THEN
		# InDesign体裁指示タグの削除
		SET x = REPLACE(x,'《rtn》','');
		SET x = REPLACE(x,'《c_G》','');
		SET x = REPLACE(x,'《/c_G》','');
		SET x = REPLACE(x,'《c_SY》','');
		SET x = REPLACE(x,'《/c_SY》','');
		SET x = REPLACE(x,'《c_SK》','');
		SET x = REPLACE(x,'《/c_SK》','');
		SET x = REPLACE(x,'《c_KJ》','');
		SET x = REPLACE(x,'《/c_KJ》','');
		SET x = REPLACE(x,'《c_KA》','');
		SET x = REPLACE(x,'《/c_KA》','');
		SET x = REPLACE(x,'《c_GAI》','');
		SET x = REPLACE(x,'《/c_GAI》','');
		SET x = REPLACE(x,'《c_UT》','');
		SET x = REPLACE(x,'《/c_UT》','');
		SET x = REPLACE(x,'《c_ST》','');
		SET x = REPLACE(x,'《/c_ST》','');
		SET x = REPLACE(x,'《c_UBK》','');
		SET x = REPLACE(x,'《/c_UBK》','');
		SET x = REPLACE(x,'《c_SAK》','');
		SET x = REPLACE(x,'《/c_SAK》','');
		SET x = REPLACE(x,'《HN》','');

		# ルビタグ(【】)と囲まれたフリガナの削除
		WHILE LOCATE('【',x) > 0 DO

			SET wk_before = SUBSTRING(x, 1, LOCATE('【',x)-1);
			SET wk_after = SUBSTRING(x, LOCATE('】',x)+1);

			SET x = CONCAT(wk_before,wk_after);

		END WHILE;

	END IF;
	
	RETURN x;
END;
//
