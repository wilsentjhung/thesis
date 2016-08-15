DROP TABLE IF EXISTS pre_reqs;
CREATE TABLE pre_reqs (course_code text, career text, pre_req_conditions text, norm_pre_req_conditions text);
INSERT INTO pre_reqs (course_code, career, pre_req_conditions, norm_pre_req_conditions) SELECT 'CHEN6706', 'PG', 'Pre-requisites: CEIC2001, CEIC2002, MATH2019', '(CEIC2001 && CEIC2002 && MATH2019)' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = 'CHEN6706' and career = 'PG'); 
