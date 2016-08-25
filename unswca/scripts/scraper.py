#!/usr/bin/python

#need course title and uoc

import urllib2
import re
#from bs4 import BeautifulSoup

#find how many courses left to go
#select count(*)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'CVEN1000';
#find the courses
#select course_code, career, left(pre_req_conditions,120) as c, left(norm_pre_req_conditions,120)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'CVEN1000';
#select course_code, career, left(pre_req_conditions,90) as c, left(norm_pre_req_conditions,90)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'MARK1000';

#find a specific course to show the full length conditions
#select * from pre_reqs where course_code like '%COMP4930%';

#select course_code, career, left(pre_req_conditions,90) as c, left(norm_pre_req_conditions,85)  from pre_reqs  where course_code > 'OPTM7000' order by course_code;

allCourseCode = set()
codePattern = re.compile(r"[A-Z]{4}[0-9]{4}")
prereqSentence = re.compile(r"<p>[pP]re.*?<\/p>")
prereqOnlySentence = re.compile(r"<p>([pP]re.*?)([pP]requisite)?([cC]o-?[Rr]eq.*?)?([Ee]xcl.*?)?<\/p>")
coreqSentence = re.compile(r"([cC]o[ \-][rR]eq.*?)(\.|<|Excl|Equi)")
coreqOnlySentence = re.compile(r"([cC]o[ \-][rR]eq.*)")
equiSentence = re.compile(r"<p>.*?([Ee]quivalent:.*?)<\/p>")
equiOnlySentence = re.compile(r".*?([Ee]quivalent:.*)")
exclSentence = re.compile(r"<p>.*?([Ee]xclu.*?:.*?)<\/p>")
exclOnlySentence = re.compile(r".*?([Ee]xclu.*?:.*)")
uocPattern = re.compile(r"<p><strong>Units of Credit:<\/strong>.*?([0-9]+)<\/p>")
titlePattern = re.compile(r"<title>.*?-\s*(.*?)\s*- [A-Z]{4}[0-9]{4}<\/title>",  re.DOTALL)

f = open("pre_reqs.sql", "w")
f.write("DROP TABLE IF EXISTS pre_reqs;\n")
f.write("CREATE TABLE pre_reqs (course_code text, title text, uoc integer, career text, pre_req_conditions text, norm_pre_req_conditions text);\n")

g = open("co_reqs.sql", "w")
g.write("DROP TABLE IF EXISTS co_reqs;\n")
g.write("CREATE TABLE co_reqs (course_code text, title text, uoc integer, career text, co_req_conditions text, norm_co_req_conditions text);\n")

h = open("equivalence.sql", "w")
h.write("DROP TABLE IF EXISTS equivalence;\n")
h.write("CREATE TABLE equivalence (course_code text, title text, uoc integer, career text, equivalence_conditions text, norm_equivalence_conditions text);\n")

i = open("exclusion.sql", "w")
i.write("DROP TABLE IF EXISTS exclusion;\n")
i.write("CREATE TABLE exclusion (course_code text, title text, uoc integer, career text, exclusion_conditions text, norm_exclusion_conditions text);\n")

ugUrl = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
ugHtml = urllib2.urlopen(ugUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/2016/[A-Z]{4}[0-9]{4}\.html")
subjectCode = re.findall(subjectCodeWebsite, ugHtml)

pgUrl = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
pgHtml = urllib2.urlopen(pgUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/2016/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, pgHtml)

ugUrl = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
ugHtml = urllib2.urlopen(ugUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/2015/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, ugHtml)

pgUrl = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
pgHtml = urllib2.urlopen(pgUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/2015/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, pgHtml)

for hc in subjectCode:
	career = "UG"
	if "postgraduate" in hc:
		career = "PG"

	url2 = hc
	#print hc
	prereq = ""
	prereqCondition = ""
	coreq = ""
	coreqCondition = ""
	equivalence = ""
	equivalenceCondition = ""
	exclusion = ""
	exclusionCondition = ""
	uoc = ""
	title = ""

	try:
		codeInUrl = re.findall(codePattern, url2)
		html2 = urllib2.urlopen(url2).read()
		
		#print "debug"
		courseCode = re.findall(prereqSentence, html2)
		courseCode2 = re.findall(coreqSentence, html2)
		courseCode3 = re.findall(equiSentence, html2)
		courseCode4 = re.findall(exclSentence, html2)
		uoc = int(uocPattern.search(html2).group(1))
		title = titlePattern.search(html2).group(1)
		title = re.sub(r"\'", "\'\'", title, flags=re.IGNORECASE)

		if courseCode3:
			#print "trying"
			equivalence = equiOnlySentence.search(courseCode3[0]).group(0)

		if courseCode4:
			exclusion = exclOnlySentence.search(courseCode4[0]).group(0)

		if courseCode:
			prereq = prereqOnlySentence.search(courseCode[0]).group(1)
			coreq = prereqOnlySentence.search(courseCode[0]).group(3)

			prereqCondition = prereq
			prereq = re.sub(r"\'", "\'\'", prereq, flags=re.IGNORECASE)
			prereqCondition = re.sub(r"\'", "\'\'", prereqCondition, flags=re.IGNORECASE)

			#remove prereq word
			prereq = re.sub(r"Pre(.*?:|requisite)", "(", prereq, flags=re.IGNORECASE)

			#change to ands
			prereq = re.sub(r"\sAND\s", " && ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r"\s&\s", " && ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r"\sincluding\s", " && ", prereq, flags=re.IGNORECASE)

			#comma can mean and/or
			prereq = re.sub(r",\s*or", " || ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r",", " && ", prereq, flags=re.IGNORECASE)

			#change to ors
			prereq = re.sub(r';', ' || ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\sOR\s', ' || ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\/', ' || ', prereq, flags=re.IGNORECASE)

			#change to uoc
			prereq = re.sub(r'\s*uoc\b', '_UOC', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*uc\b', '_UOC', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', prereq, flags=re.IGNORECASE)

			#remove unnecessary words
			prereq = re.sub(r'\.', '', prereq, flags=re.IGNORECASE)

			#change [] to ()
			prereq = re.sub(r'\[', '(', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\]', ')', prereq, flags=re.IGNORECASE)



			prereq = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'approval from the School', "SCHOOL_APPROVAL", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'school approval', "SCHOOL_APPROVAL", prereq, flags=re.IGNORECASE)
			#prereq = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'Enrolment in program ([0-9]+)', r'\1', prereq, flags=re.IGNORECASE)

			#prereq = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', 
				#"(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", prereq, flags=re.IGNORECASE)
			#prereq = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s*[^a-zA-Z0-9]*$', r'\1_UOC_LEVEL_\2', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s+([A-Za-z])', r'\1_UOC_LEVEL_\2_\3', prereq, flags=re.IGNORECASE)

			prereq = re.sub(r'stream', '', prereq, flags=re.IGNORECASE)

			#cleanup
			prereq = re.sub(r'&&\s*&&$', '&&', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'&&\s*$', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\|\|\s*$', ' ', prereq, flags=re.IGNORECASE)
			prereq += ")"
			prereq = re.sub(r'\(\s*\)', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\(\s*', '(', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*\)', ')', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s\s+', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\'', '\'\'', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'^\s+$', '', prereq, flags=re.IGNORECASE)
			#print prereq


			#manual
			if (codeInUrl[0] == "ACCT2507"):
				prereq = "(ACCT1511{80})"
			elif (codeInUrl[0] == "ACCT4794" or codeInUrl[0] == "ACCT4809" or codeInUrl[0] == "ACCT4851" or
				codeInUrl[0] == "ACCT4852" or codeInUrl[0] == "ACCT4897"):
				#!!!
				prereq = "(ACCOUNTING_HONOURS)"
			elif (codeInUrl[0] == "ACCT5919" or codeInUrl[0] == "ACCT5996"):
				#actually can be a coreq
				prereq = "(ACCT5930 || COMM5003 || ACCT5906)"
			elif (codeInUrl[0] == "ACCT5967"):
				prereq = "(ACCT5997)"
			elif (codeInUrl[0] == "ACTL1101"):
				prereq = "(MATH1151 && (3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737))"
			elif (codeInUrl[0] == "ACTL2102"):
				prereq = "((ACTL2131 || MATH2901) && (3154 || 3155 || 3586 || 3587 || 3588 || 3589 || 4737))"
			elif (codeInUrl[0] == "ACTL3162"):
				prereq = "(ACTL2102 || (MATH2901 && (MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)))"
			elif (codeInUrl[0] == "ACTL4000" or codeInUrl[0] == "ACTL4003"):
				#!!!
				prereq = "(ACTUARIAL_HONOURS)"
			elif (codeInUrl[0] == "ACTL4002"):
				#changed prereq and coreq
				prereq = "(ACTL4001)"
			elif (codeInUrl[0] == "ACTL4303"):
				#SPECIAL CONDITION
				prereq = "(ACTL3141 || ACTL4001)"
			elif (codeInUrl[0] == "ACTL5103" or codeInUrl[0] == "ACTL5104" or codeInUrl[0] == "ACTL5106"):
				prereq = "(ACTL5101 && (8411 || 8416))"
			elif (codeInUrl[0] == "ACTL5105" or codeInUrl[0] == "ACTL5109"):
				#needs coreq for 5109
				prereq = "(ACTL5101 && ACTL5102 && (8411 || 8416))"
			#ACTL5200 needs coreq
			elif (codeInUrl[0] == "ACTL5401"):
				prereq = "(7273 || 5273 || 9273 || SCHOOL_APPROVAL)"
			#AERO3640 needs coreq
			elif (codeInUrl[0] == "ANAT2111"):
				prereq = "(BABS1201 && (ANAT2241 || BABS1202 || BABS2202 || BABS2204 || BIOC2201 || BIOC2291 || BIOS1101 || HESC1501 || PHSL2101 || PHSL2121 || PHSL2501 || VISN1101))"
			elif (codeInUrl[0] == "ANAT2241"):
				prereq = "(BABS1201 && PROGRAM_WAM_55)"
			elif (codeInUrl[0] == "ARCH1201"):
				prereq = "((ARCH1101 && ARCH1102) || ARCH1390)"
			elif (codeInUrl[0] ==  "ARCH1302"):
				prereq = "(ARCH1202 && ARCH1301)"
			elif (codeInUrl[0] ==  "ARCH1395"):
				prereq = "(ARCH1394 && ARCH1384)"
			elif (codeInUrl[0] == "ARTS1006"):
				prereq = "(ARTS1005{CR})"
			elif (codeInUrl[0] == "ARTS2006"):
				prereq = "(MUSC1704 || MUSC1705 || MUSC1706 || ARTS1005 || 3425 || 3426 || 3427 || 3448 || 3449)"
			elif (codeInUrl[0] == "ARTS2007"):
				prereq = "(30_UOC_LEVEL_1 && ARTS1005)"
			elif (codeInUrl[0] == "ARTS2038"):
				prereq = "(30_UOC && 12_UOC_LEVEL_1_ENGLISH)"
			elif (codeInUrl[0] == "ARTS2050"):
				#!!!
				prereq = "(12_UOC_LEVEL_1 && (enrolment in an Arts and Social Sciences || Art and Design program))"
			elif (codeInUrl[0] == "ARTS2065"):
				prereq = "(30_UOC_LEVEL_1 && (ARTS1060 || ARTS1062))"
			elif (codeInUrl[0] == "ARTS2195"):
				prereq = "(30_UOC at Level 1 && (ARTS1190 || ARTS1270 || ARTS1900))"
			elif (codeInUrl[0] == "ARTS2452"):
				#???
				prereq = "(ARTS3451 || ARTS3452 || ARTS3453)"
			elif (codeInUrl[0] == "ARTS2690" or codeInUrl[0] == "ARTS2692" or codeInUrl[0] == "ARTS2693" 
				or codeInUrl[0] == "ARTS2694" or codeInUrl[0] == "ARTS2696"):
				prereq = "(30_UOC && 12_UOC_LEVEL_1_LINGUISTICS)"

			##ARTS[345]### skipped
			elif (codeInUrl[0] == "ATSI3008"):
				#!!!
				prereq = "(120_UOC && enrolment in a major in Indigenous Studies && enrolled in the final semester of an Arts Faculty)"
			elif (codeInUrl[0] == "AVIA2013"):
				prereq = "((AVIA1401 && AVIA1901 && MATH1041) || (AVIA1401 && AVIA1901 && PHYS1211) || (AVIA1401 && MATH1041 && PHYS1211) || (AVIA1901 && MATH1041 && PHYS1211))"
			elif (codeInUrl[0] == "AVIA3101"):
				prereq = "(AVIA1900 || AVIA2004 || AVIA2014 || AVIA1901)"
			elif (codeInUrl[0] == "AVIA3851"):
				prereq = "(AVIA1850 || AVIA2701)"
			#AVIG5911. pass flight screening
			elif (codeInUrl[0] == "BABS2011"):
				#!!!
				prereq = "(BABS1201 || BABS1202 && enrolment in relevant Science program)"
			elif (codeInUrl[0] == "BABS2202"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031))"
			elif (codeInUrl[0] == "BABS3021"):
				prereq = "((MICR2011 && BIOS2021) || (MICR2011 && BABS2204) || (MICR2011 && BIOS2621) || (MICR2011 && BABS2264) || (MICR2011 && BIOC2201) || (BIOS2021 && BABS2204) || (BIOS2021 && BIOS2621) || (BIOS2021 && BABS2264) || (BIOS2021 && BIOC2201) || (BABS2204 && BIOS2621) || (BABS2204 && BABS2264) || (BABS2204 && BIOC2201) || (BIOS2621 && BABS2264) || (BIOS2621 && BIOC2201) || (BABS2264 && BIOC2201)"

			elif (codeInUrl[0] == "BABS3631" or codeInUrl[0] == "BABS3031"):
				prereq = "(48_UOC)"
			elif (codeInUrl[0] == "BABS3041"):
				prereq = "(BIOC2101 || (BIOC2181 && MICR2011) || (BIOC2181 && BABS2202))"
			elif (codeInUrl[0] == "BABS3061" or codeInUrl[0] == "BABS3121" or codeInUrl[0] == "BIOC3111"):
				prereq = "((BIOC2101 || LIFE2101) && BIOC2201)"
			elif (codeInUrl[0] == "BABS3621"):
				prereq = "(BIOC2101 && BIOC2201 && (3985 || 3990 || 3972 || 3973 || 3986 || 3931 || 3936))"
			elif (codeInUrl[0] == "BABS4053"):
				prereq = "(144_UOC && 3052)"
			elif (codeInUrl[0] == "BABS6741" and career == "UG"):
				prereq = "(30_UOC_SCIENCE)"
			elif (codeInUrl[0] == "BEES6741" and career == "UG"):
				prereq = "(30_UOC_SCIENCE)"
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0]) or re.match('BLDG', codeInUrl[0]) or re.match('BENV', codeInUrl[0])) and re.match('\(96_UOC completed in Built Environment', prereq) and career == "UG"):
				prereq = "(96_UOC_BUILT_ENVIORNMENT)"
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0]) or re.match('BENV', codeInUrl[0])) and re.match('\(96_UOC completed', prereq) and career == "UG"):
				prereq = "(96_UOC)"

			elif (codeInUrl[0] == "BINF4910"):
				prereq = "(126_UOC && (3647 || 3755 || 3756 || 3757 || 3715))"
			elif (codeInUrl[0] == "BINF4920"):
				prereq = "(3647 || 3755 || 3756 || 3757 || 3715)"
			elif (codeInUrl[0] == "BIOC2101" or codeInUrl[0] == "BIOC2201"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061))"
			elif (codeInUrl[0] == "BIOC2291"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031))"
			elif (codeInUrl[0] == "BIOC3671"):
				prereq = "(BIOC2101 && BIOC2201 && (3985 || 3990 || 3972 || 3973 || 3986 || 3931 || 3936))"
			elif (codeInUrl[0] == "BIOM5950"):
				prereq = "(126_UOC_3728 || 126_UOC_3757)"
			elif (codeInUrl[0] == "BIOM5960"):
				prereq = "(STAGE_4_3749)"
			elif (codeInUrl[0] == "BIOM9510"):
				prereq = "!(3710 || 3711 || 3683 || 3688)"
			elif (codeInUrl[0] == "BIOM9670"):
				prereq = "(BIOM9660{DN})"
			elif (codeInUrl[0] == "BIOS3711"):
				prereq = "((ANAT2111 || ANAT1521 || ANAT2511{CR} || ANAT1551) && PROGRAM_WAM_50)"
			elif (codeInUrl[0] == "CEIC2004"):
				prereq = "(CHEM1021 || CHEM1041 || CEIC1001)"
			elif ((re.match('CEIC', codeInUrl[0]) or re.match('CHEM', codeInUrl[0]) or re.match('CHEN', codeInUrl[0])) and re.match('\(at least 144 Units', prereq) and career == "UG"):
				prereq = "(144_UOC_CHEMICAL_ENGINEERING || 144_UOC_INDUSTRIAL_CHEMICAL)"
			elif (codeInUrl[0] == "CEIC6005"):
				prereq = "((MATS1101 || CHEM1011 || CHEM1021) && CEIC2000 && CEIC2002)"
			elif (codeInUrl[0] == "CHEM1041"):
				prereq = "(CHEM1031 || CHEM1011{CR})"
			elif (codeInUrl[0] == "CHEM1051"):
				prereq = "(3999 || 3992)"
			elif (codeInUrl[0] == "CHEM1061"):
				prereq = "((3992 || 3999) && (CHEM1051 || CHEM1031 || CHEM1011{CR}))"
			elif (codeInUrl[0] == "CHEM1829"):
				#!!!
				prereq = "(CHEM1031 && (3952 || VISION_SCIENCE))"
			elif (codeInUrl[0] == "CHEM2011"):
				prereq = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061) && (MATH1011 || MATH1031 || MATH1131 || MATH1141 || MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "CHEM2021" or codeInUrl[0] == "CHEM2031" or codeInUrl[0] == "CHEM2921"):
				prereq = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061))"
			elif (codeInUrl[0] == "CHEM2041"):
				prereq = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061) && (MATH1031 || MATH1041 || MATH1131 || MATH1141 || MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "CHEM2828" or codeInUrl[0] == "CHEM2839"):
				prereq = "((CHEM1011 || CHEM1031) && (CHEM1021 || CHEM1041))"
			elif (codeInUrl[0] == "CODE3100"):
				#!!!
				prereq = "(year 1 && 2 core courses)"
			#COMD5004
			elif (codeInUrl[0] == "COMM5002"):
				#can be a coreq
				prereq = "(COMM5001)"
			elif (codeInUrl[0] == "COMM5004"):
				prereq = "(COMM5001 && COMM5002 && COMM5003 && (8404 || 8417) && 48_UOC)"
			elif (codeInUrl[0] == "COMM5008"):
				prereq = "(8417 || 8404 || 8415)"
			elif (codeInUrl[0] == "COMP1400" or codeInUrl[0] == "COMP1911"):
				#!!!
				prereq = "!(CSE_PROGRAM)"
			elif (codeInUrl[0] == "COMP2111"):
				prereq = "((COMP1911 || COMP1917) && MATH1081)"
			elif (codeInUrl[0] == "COMP2121"):
				prereq = "(COMP1917 || COMP1921 || (COMP1911 && MTRN2500))"
			elif (codeInUrl[0] == "COMP3231"):
				#!!!
				prereq = "(((COMP1921 || COMP1927) && (COMP2121 || ELEC2142)) || (COMP1921 || COMP1927))"
			elif (codeInUrl[0] == "COMP3431"):
				prereq = "(COMP2911 && 70_WAM)"
			elif (codeInUrl[0] == "COMP3441" or codeInUrl[0] == "COMP3511" or (codeInUrl[0] == "COMP4161" and career == "UG")):
				prereq = "(48_UOC)"
			elif (codeInUrl[0] == "COMP3821"):
				prereq = "(COMP1927 || COMP1921{70})"
			elif (codeInUrl[0] == "COMP3891"):
				prereq = "((COMP1921{70} || COMP1927) && (COMP2121{70} || ELEC2142))"
			elif (codeInUrl[0] == "COMP3901" or codeInUrl[0] == "COMP3902"):
				#!!!!
				prereq = "(80_CSE_WAM)"
			elif (codeInUrl[0] == "COMP4411"):
				prereq = "(75_WAM && COMP1927)"
			elif (codeInUrl[0] == "COMP4431" and career == "UG"):
				#!!!
				prereq = "(COMP1927 || (Stage_2{3267 || 3994 || 3402 || 3428 || 4810 || 4802}))"

			elif (codeInUrl[0] == "COMP4904"):
				prereq = "(72_UOC_COMPUTER_SCIENCE_CO_OP)"
			elif (codeInUrl[0] == "COMP4910"):
				#!!!
				prereq = "(COMPUTER_SCIENCE_HONOURS)"
			elif (codeInUrl[0] == "COMP4920"):
				#!!!
				prereq = "(CSE_STAGE_2)"
			elif (codeInUrl[0] == "COMP4930"):
				#!!!
				prereq = "(4515 || 126_UOC_CSE)"
			elif (codeInUrl[0] == "COMP4941"):
				#!!!
				prereq = "(COMP4930 && (75_WAM || COMPUTER_SCIENCE_HONOURS))"
			elif (codeInUrl[0] == "COMP6721" and career == "UG"):
				prereq = "((MATH1081 || 6_UOC_MATH2###) && 12_UOC_COMP3###)"
			elif (codeInUrl[0] == "COMP6733"):
				prereq = "(65_WAM && COMP3331)"
			elif (codeInUrl[0] == "COMP9018" and career == "UG"):
				prereq = "(COMP3421{65})"
			elif (codeInUrl[0] == "COMP9018" and career == "PG"):
				prereq = "(COMP9415{65})"
			elif (codeInUrl[0] == "COMP9242" and career == "UG"):
				prereq = "(COMP3231{75} || COMP3891)"
			elif (codeInUrl[0] == "COMP9242" and career == "PG"):
				prereq = "(COMP9201{75} || COMP9283)"

			elif (codeInUrl[0] == "COMP9283"):
				prereq = "(COMP9032{70} && COMP9024)"
			#COMP9321 has coreq for postgrad
			elif (codeInUrl[0] == "COMP9321" and career == "PG"):
				prereq = "(COMP9024)"
			elif (codeInUrl[0] == "COMP9431"):
				prereq = "(70_WAM && COMP9024)"
			elif (codeInUrl[0] == "COMP9801"):
				prereq = "(COMP9024{70})"

			elif (codeInUrl[0] == "COMP9844" and career == "UG"):
				prereq = "(70_WAM && (COMP1927 || MTRN3500))"
			elif (codeInUrl[0] == "COMP9844" and career == "PG"):
				prereq = "(70_WAM && COMP9024)"
			elif (codeInUrl[0] == "CRIM2014" or codeInUrl[0] == "CRIM2031" or codeInUrl[0] == "CRIM2032" or codeInUrl[0] == "CRIM2034" or codeInUrl[0] == "CRIM2036" or codeInUrl[0] == "CRIM2037" or codeInUrl[0] == "CRIM2038"):
				prereq = "(30_UOC_LEVEL_1_CRIMINOLOGY && (CRIM1010 || CRIM1011))"
			elif (codeInUrl[0] == "CRIM2020"):
				prereq = "(24_UOC_LEVEL_1_CRIMINOLOGY && CRIM1010 && CRIM1011)"
			elif (codeInUrl[0] == "CRIM2021"):
				prereq = "(CRIM2020 && !(LAW))"
			#elif (codeInUrl[0] == "CRIM2021"):
		#		prereq = "(30_UOC_LEVEL_1_CRIMINOLOGY)"
				#CRIM3### skipped
			elif (re.match('CRIM4', codeInUrl[0])):
				prereq = "(CRIMINOLOGY_HONOURS)"
				#CRIM5### skipped
			elif (codeInUrl[0] == "CVEN4002" or codeInUrl[0] == "CVEN4003" or codeInUrl[0] == "CVEN4050"):
				prereq = "(132_UOC)"
			elif (codeInUrl[0] == "CVEN4030"):
				prereq = "(132_UOC && 63_WAM)"
			elif (codeInUrl[0] == "CVEN4308"):
				prereq = "(CVEN3301 && CVEN2002)"
			elif (codeInUrl[0] == "ECON2107"):
				prereq = "(ECON1101 && (ECON1203 || ECON2292 || MATH1041 || MATH2801 || MATH2841 || MATH2901))"
			elif (codeInUrl[0] == "ECON3109" or codeInUrl[0] == "ECON3119"):
				prereq = "(ECON2101 || ECON2103 || 48_UOC_ARTS_SOCIAL_SCIENCES)"
			elif (codeInUrl[0] == "ECON3114" or codeInUrl[0] == "ECON3117"):
				prereq = "(ECON2101 || ACTL2131 || ACTL2111 || (84_UOC && (3155 || 3502 || 3554 || 4501 || 3558 || 3593 || 3835 || 3967 || 3568 || 3567 || 3584 || 4733 || 3522 || 3521 || 3462 || 3559 || 3529 || 3764 || 3136)"
			elif (codeInUrl[0] == "ECON5257"):
				prereq = "(8409 || 8415)"
			elif (re.match('ECON5', codeInUrl[0]) and re.match('\(7412 || approval', prereq)):
				prereq = "(7412 || SCHOOL_APPROVAL)"
			elif (re.match('ECON6', codeInUrl[0]) and re.match('\(8412 || approval', prereq)):
				prereq = "(8412 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ECON6205"):
				prereq = "(ECON6003 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ECON6301" or codeInUrl[0] == "ECON6302" or codeInUrl[0] == "ECON6303" or codeInUrl[0] == "ECON6307"):
				#it's a coreq as well
				prereq = "(ECON6001)"
			elif (codeInUrl[0] == "ECON6350"):
				#it's a coreq as well
				prereq = "(ECON6001 && ECON6002 && ECON6003 && ECON6004)"
			elif (codeInUrl[0] == "EDST2002"):
				prereq = "(48_UOC_LEVEL_1 && EDST1101 && EDST1104 && EDST2003 && (3446 || 3462 || 4054 || 4076 || 4058 || 4059 || 4061 || 4062))"
			elif (codeInUrl[0] == "EDST2003"):
				prereq = "(24_UOC_LEVEL_1 && (EDST1101 || EDST1104) && (3446 || 3462 || 4054 || 4058 || 4059 || 4061 || 4062 || 4076))"
			#elif (re.match('EDST20', codeInUrl[0]) and re.match('24', prereq)):
				#prereq = "24_UOC_LEVEL_1"
				#EDST skipped
			elif (re.match('ELEC', codeInUrl[0])):
				prereq = prereq.upper()
				if (codeInUrl[0] == "ELEC2142"):
					prereq = "(ELEC2141 && COMP1921)"

			elif (codeInUrl[0] == "FINS2622"):
				#also a coreq
				prereq = "(FINS1612 && FINS2624)"
			elif (codeInUrl[0] == "FINS3202"):
				prereq = "(FINS2101 && (FINSD13554 || FINSBH3565))"
			elif (codeInUrl[0] == "FINS3303"):
				prereq = "(FINS3202 && (FINSD13554 || FINSBH3565))"
			elif (codeInUrl[0] == "FINS3775" or codeInUrl[0] == "FINS4775"):
				prereq = "(FINS2624{70} && ECON1203)"
			elif (codeInUrl[0] == "FINS4774" or codeInUrl[0] == "FINS4776" or codeInUrl[0] == "FINS4777" or codeInUrl[0] == "FINS4779"):
				#also a coreq
				prereq = "(FINS3775 || FINS4775)"
			elif (codeInUrl[0] == "FINS4781"):
				prereq = "(FINANCE_HONOURS)"
			elif (codeInUrl[0] == "FINS5511"):
				#also a coreq
				prereq = "(ACCT5930 && ECON5103)"
			elif (codeInUrl[0] == "FINS5512"):
				#also a coreq
				prereq = "(ACCT5906 || ECON5103 || 9273 || 5273 || 7273 || 8007)"
			elif (codeInUrl[0] == "FINS5513"):
				prereq = "(COMM5005 || ECON5248 || COMM5011 || 5273 || 7273 || 8413 || 9273 || (REST0001 && 8127))"
			elif (codeInUrl[0] == "FINS5514" or codeInUrl[0] == "FINS5517"):
				#also a coreq
				prereq = "(FINS5513)"
			elif (codeInUrl[0] == "FINS5516" or codeInUrl[0] == "FINS5530" or codeInUrl[0] == "FINS5531" or codeInUrl[0] == "FINS5542"):
				#also a coreq
				prereq = "(FINS5513 || 8406)"
			elif (codeInUrl[0] == "FINS5522"):
				#also a coreq
				prereq = "((FINS5512 and FINS5513) || 8406)"
			elif (codeInUrl[0] == "FINS5533"):
				#also a coreq
				prereq = "(FINS5513 || FINS5561 || 8406)"
			elif (codeInUrl[0] == "FINS5543"):
				prereq = "(7273 || 5273 || 9273)"
			elif (codeInUrl[0] == "FINS5566"):
				#also a coreq
				prereq = "(FINS5512 || 8406 || 8413)"
			elif (codeInUrl[0] == "FINS5568"):
				prereq = "(FINS5513 && (8404 || 8417 || 8428) && 48_UOC)"
			elif (codeInUrl[0] == "FINS5579"):
				#also a coreq
				prereq = "(FINS3775 || FINS4775 || FINS5575)"

			elif (codeInUrl[0] == "FOOD6804"):
				prereq = "(CHEM3811 || INDC2003)"

			elif (codeInUrl[0] == "GBAT9104" or codeInUrl[0] == "GBAT9113"):
				prereq = "((8616 && 48_UOC) || (5457 && 36_UOC))"
			elif (re.match('GBAT', codeInUrl[0]) and re.match('\(', prereq)):
				prereq = "(8616 || 7333 || 5457)"
			elif (codeInUrl[0] == "GEOL4141"):
				prereq = "(24_UOC_LEVEL_3_GEOLOGY || 24_UOC_LEVEL_3_PHYSICAL_GEOGRAPHY)"
			elif (codeInUrl[0] == "GEOS2101"):
				#!!!
				prereq = "(GEOL1111 || GEOS1111 || GEOL1211 || GEOS1211 || GEOS1701 || BIOS1101)"
			elif (codeInUrl[0] == "GEOS3371"):
				prereq = "(GEOS3331 && LEVEL_3_GEOSCIENCE)"
			elif (codeInUrl[0] == "GEOS3621"):
				prereq = "((GEOS2641 || ENVS2030) && GEOS2721)"
			elif (codeInUrl[0] == "GEOS6734"):
				prereq = "(BEES2041 || MATH2089 || MATH2099 || MATH2801 || MATH2841 || MATH2859 || MATH2901)"
			elif (codeInUrl[0] == "HESC2452"):
				prereq = "((ANAT2451 || (ANAT3131 && ANAT3141)) && (BIOM2451 || SESC2451))"
			elif (codeInUrl[0] == "HESC2501"):
				prereq = "(BIOC2181 && PHSL2501)"
			elif (codeInUrl[0] == "HESC3504"):
				prereq = "(HESC2501 && HESC1511 && (PSYC1011 || HESC1531))"
			elif (codeInUrl[0] == "HESC3541"):
				prereq = "(HESC2501 && PHSL2502 && (PATH2202 || PATH2201) && PHSL2501)"
			elif (codeInUrl[0] == "IDES4321" or codeInUrl[0] == "IDES4372"):
				prereq = "(96_UOC_BUILT_ENVIRONMENT)"
			elif (codeInUrl[0] == "INDC2003"):
				prereq = "((CHEM1021 || CHEM1041) || CEIC1001)"
			elif (codeInUrl[0] == "INFS2101"):
				prereq = "(INFS1609 && INFS2603 && (INFSB13554 || INFSCH3964 || INFSCH3971))"
			elif (codeInUrl[0] == "INFS3202"):
				prereq = "(INFS2101 && (INFSB13554 || INFSCH3971 || INFSCH3964))"
			elif (codeInUrl[0] == "INFS3303"):
				prereq = "(INFS3202 && (INFSB13554 || INFSCH3971 || INFSCH3964))"
			elif (codeInUrl[0] == "INFS3603" or codeInUrl[0] == "INFS3604" or codeInUrl[0] == "INFS3631" or codeInUrl[0] == "INFS3632" or codeInUrl[0] == "INFS3633"):
				prereq = "(INFS1602 && 72_UOC)"
			elif (codeInUrl[0] == "INFS3605"):
				prereq = "(INFS2603 && INFS2605 && 72_UOC)"
			elif (codeInUrl[0] == "INFS3608"):
				prereq = "((INFS1602 && INFS1603) || SOFTWARE_ENGINEERING_PROGRAM)"
			elif (codeInUrl[0] == "INFS3611"):
				prereq = "(INFS2603 && (INFS1609 || INFS2609) && 72_UOC)"
			elif (codeInUrl[0] == "INFS3634"):
				prereq = "(INFS2605 && INFS2608 && 72_UOC)"
			elif (codeInUrl[0] == "INFS3774"):
				prereq = "(INFS2607 && 72_UOC)"
			elif (codeInUrl[0] == "INFS4796"):
				prereq = "(INFS4795 && (INFSCH3971 || INFSBH3554 || INFSAH3502 || INFSAH3979 || INFSAH3584))"
			elif (codeInUrl[0] == "INFS4887"):
				#!!!
				prereq = "(INFORMATION_SYSTEMS_HONOURS && INFS4886)"
			elif (codeInUrl[0] == "INFS4795" or re.match('INFS48', codeInUrl[0])):
				#!!!
				prereq = "(INFORMATION_SYSTEMS_HONOURS)"
			elif (codeInUrl[0] == "INFS5731" or codeInUrl[0] == "INFS5732"):
				prereq = "(8407 || 8435 || 8425 || 8426)"
			elif (codeInUrl[0] == "INFS5740"):
				prereq = "(8407 || 8425 || 8435 || 8426 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "INFS5935"):
				prereq = "(8425 || 8435)"
			elif (codeInUrl[0] == "INFS5936"):
				prereq = "(8435)"
			elif (codeInUrl[0] == "INFS5978"):
				#also a coreq
				prereq = "(ACCT5930)"
			elif (codeInUrl[0] == "INFS5997"):
				#!!!
				prereq = "((8404 || 8417) && 75_WAM)"
			elif (codeInUrl[0] == "INST1005"):
				#!!!
				prereq = "(INTERNATIONAL_STUDIES)"
			elif (codeInUrl[0] == "INST3900"):
				#!!!
				prereq = "(96_UOC && INTERNATIONAL_STUDIES)"
			#JAPN5011 skipped
			#JURD skipped
			#KORE skipped
			#LAWS skipped
			#LING skipped

			elif (codeInUrl[0] == "MANF3610"):
				prereq = "((ENGG1811 || COMP1911) && MATH2089)"

			elif (codeInUrl[0] == "MANF4100"):
				prereq = "(MANF3100 && MANF3510)"
			elif (codeInUrl[0] == "MANF4430"):
				prereq = "(MATH2089)"
			
			elif (codeInUrl[0] == "MARK3202" or codeInUrl[0] == "MARK3303"):
				prereq = "(MARK2101 && MARKB13554)"
			elif (re.match('MARK42', codeInUrl[0])):
				#!!!
				prereq = "(MARKETING_HONOURS)"
			elif (codeInUrl[0] == "MARK5810" or codeInUrl[0] == "MARK5812" or codeInUrl[0] == "MARK5815" or codeInUrl[0] == "MARK5817"):
				#also a coreq
				prereq = "(MARK5800 || MARK5801 || MARK5813)"
			elif (codeInUrl[0] == "MARK5811" or codeInUrl[0] == "MARK5813" or codeInUrl[0] == "MARK5822"):
				#also a coreq
				prereq = "(MARK5700 || MARK5800 || MARK5801)"
			elif (codeInUrl[0] == "MARK5814"):
				#also a coreq
				prereq = "(MARK5700 || MARK5800 || MARK5801 || MARK5813)"
			elif (codeInUrl[0] == "MARK5816"):
				#also a coreq
				prereq = "(MARK5800 || MARK5801 || MARK5813 || 8406)"
			elif (codeInUrl[0] == "MARK5819"):
				prereq = "(MARK5800 || MARK5801 || (7291 || 5291 || 8291 || 8281))"
			elif (codeInUrl[0] == "MARK5820"):
				#also a coreq
				prereq = "(MARK5800 || MARK5801 || (7291 || 5291 || 8291 || 8281))"
			elif (re.match('MARK60', codeInUrl[0]) and re.match('\(', prereq)):
				prereq = "(MARKETING_POSTGRADUATE)"
			elif (re.match('MARK61', codeInUrl[0]) and re.match('\(', prereq)):
				prereq = "(7414 || 8423)"
			elif (codeInUrl[0] == "MATH1241"):
				prereq = "(MATH1131{CR} || MATH1141{CR})"
			elif (codeInUrl[0] == "MATH2111" or codeInUrl[0] == "MATH2130" or codeInUrl[0] == "MATH2221" or codeInUrl[0] == "MATH2601" or codeInUrl[0] == "MATH2620" or codeInUrl[0] == "MATH2621"):
				prereq = "(MATH1231{70} || MATH1241{70} || MATH1251{70})"
			elif (codeInUrl[0] == "MATH2301"):
				prereq = "(MATH1031{CR} || MATH1231 || MATH1241 || MATH1251)"
			elif (codeInUrl[0] == "MATH2701"):
				#!!!
				prereq = "(MATH1231{CR} || MATH1241{CR} || MATH1251{CR} && enrolment in an advanced maths || advanced science program)"
			elif (codeInUrl[0] == "MATH2801" or codeInUrl[0] == "MATH2901"):
				prereq = "(MATH1231 || MATH1241 || MATH1251 || (3653 && (MATH1131 || MATH1141)))"
			elif (codeInUrl[0] == "MATH2859"):
				prereq = "(MATH1231 || MATH1241 || ((3648 || 3651 || 3652 || 3653 || 3749 || 3982) && (MATH1131 || MATH1141)))"
			elif (codeInUrl[0] == "MATH2871"):
				#???
				prereq = "(MATH1041 || ECON1203 || ECON2292 || PSYC2001 || MATH1231 || MATH1241 || MATH1251 || equivalent)"
			elif (codeInUrl[0] == "MATH2881"):
				prereq = "(MATH1231 || MATH1241 || MATH1251 || ECON1203{CR})"
			elif (codeInUrl[0] == "MATH2931"):
				prereq = "(MATH2901 || MATH2801{DN})"
			elif (re.match('MATH30', codeInUrl[0]) or codeInUrl[0] == "MATH3511" or codeInUrl[0] == "MATH3521" or codeInUrl[0] == "MATH3570"):
				prereq = "(12_UOC_LEVEL_2_MATH)"
			elif (codeInUrl[0] == "MATH3101"):
				prereq = "(12_UOC_LEVEL_2_MATH && (((MATH2011 || MATH2111) && (MATH2120 || MATH2130 || MATH2121 || MATH2221)) || (MATH2019{DN} && MATH2089) || (MATH2069{CR} && MATH2099)))"
			elif (codeInUrl[0] == "MATH3121" or codeInUrl[0] == "MATH3261"):
				prereq = "(12_UOC_LEVEL_2_MATH && (((MATH2011 || MATH2111) && (MATH2120 || MATH2130 || MATH2121 || MATH2221)) || (MATH2019{DN} && MATH2089) || (MATH2069{DN} && MATH2099)))"	
			elif (codeInUrl[0] == "MATH3161"):
				prereq = "(12_UOC_LEVEL_2_MATH && (((MATH2011 || MATH2111 || MATH2510) && (MATH2501 || MATH2601)) || (MATH2019{DN} && MATH2089) || (MATH2069{CR} && MATH2099)))"	
			elif (codeInUrl[0] == "MATH3411"):
				prereq = "(MATH1081 || MATH1231{CR} || MATH1241{CR} || MATH1251{CR} || MATH2099)"
			elif (codeInUrl[0] == "MATH3531"):
				prereq = "(12_UOC_LEVEL_2_MATH && (MATH2011 || MATH2111 || MATH2069))"
			elif (codeInUrl[0] == "MATH3560"):
				prereq = "(6_UOC_LEVEL_2_MATH)"
			elif (codeInUrl[0] == "MATH3611"):
				#check
				prereq = "((12_UOC_LEVEL_2_MATH && PROGRAM_WAM_70 && (MATH2111 || MATH2011{CR} || MATH2510{CR}) || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "MATH3701"):
				prereq = "((12_UOC_LEVEL_2_MATH && PROGRAM_WAM_70 && (((MATH2111 || MATH2011{CR} || MATH2510{CR}) && (MATH2601 || MATH2501{CR}))) || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "MATH3711"):
				prereq = "((12_UOC_LEVEL_2_MATH && PROGRAM_WAM_70 && (MATH2601 || MATH2501{CR})) || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "MATH3801"):
				prereq = "((MATH2501 || MATH2601) && (MATH2011 || MATH2111 || MATH2510 || MATH2610) && (MATH2801 || MATH2901))"
			elif (codeInUrl[0] == "MATH3851"):
				prereq = "((MATH2801 || MATH2901) && (MATH2831 || MATH2931))"
			elif (codeInUrl[0] == "MATH3901"):
				prereq = "((MATH2901 || MATH2801{DN}) && (MATH2501 || MATH2601) && (MATH2011 || MATH2111 || MATH2510 || MATH2610))"
			elif (codeInUrl[0] == "MATH3911"):
				prereq = "(MATH2931 || MATH2831{DN})"
			elif (codeInUrl[0] == "MBAX6271"):
				prereq = "(5950 || 7315 || 8616)"
			elif (codeInUrl[0] == "MBAX6272" or codeInUrl[0] == "MBAX6273"):
				prereq = "(7315 || 8616 || 8625)"
			elif (codeInUrl[0] == "MBAX6274"):
				prereq = "((MNGT6271 || MBAX6271) && (5950 || 7315 || 8355 || 8616 || 8625))"
			elif (codeInUrl[0] == "MBAX9104"):
				prereq = "(8625 && 48_UOC)"
			#MDIA skipped
			elif (codeInUrl[0] == "MFIN6213"):
				prereq = "(MFIN6201 && MFIN6210 && 8406 && 85_WAM)"
			elif (codeInUrl[0] == "MGMT2101"):
				prereq = "(MGMT1101)"
			elif (codeInUrl[0] == "MGMT2105"):
				prereq = "(MGMT1101 && 48_UOC)"
			elif (codeInUrl[0] == "MGMT2718" or codeInUrl[0] == "MGMT3728"):
				#changed
				prereq = "(3_UOC_LEVEL_1_MGMT)"
			elif (codeInUrl[0] == "MGMT3001"):
				prereq = "(MGMT1001 || 12_UOC_BUSINESS)"
			elif (codeInUrl[0] == "MGMT3003"):
				prereq = "(48_UOC)"
			elif (codeInUrl[0] == "MGMT4101" or codeInUrl[0] == "MGMT4500" or codeInUrl[0] == "MGMT4501"):
				prereq = "(4501 && INTERNATIONAL_BUSINESS_HONOURS)"
			elif (codeInUrl[0] == "MGMT4103" or codeInUrl[0] == "MGMT4750" or codeInUrl[0] == "MGMT4751"):
				prereq = "(4501 && MANAGEMENT_HONOURS)"
			elif (codeInUrl[0] == "MGMT4104" or codeInUrl[0] == "MGMT4738" or codeInUrl[0] == "MGMT4739"):
				prereq = "(4501 && HUMAN_RESOURCE_MANAGEMENT_HONOURS)"
			elif (codeInUrl[0] == "MGMT5980" or codeInUrl[0] == "MGMT5981"):
				prereq = "(8407)"
			elif (codeInUrl[0] == "MICR3621"):
				prereq = "((MICR2011 && BIOS2021) || (MICR2011 && BABS2204) || (MICR2011 && BIOS2621) || (MICR2011 && BABS2264) || (MICR2011 && BIOC2201) || (BIOS2021 && BABS2204) || (BIOS2021 && BIOS2621) || (BIOS2021 && BABS2264) || (BIOS2021 && BIOC2201) || (BABS2204 && BIOS2621) || (BABS2204 && BABS2264) || (BABS2204 && BIOC2201) || (BIOS2621 && BABS2264) || (BIOS2621 && BIOC2201) || (BABS2264 && BIOC2201)"
			elif ((re.match('MINE5', codeInUrl[0]) or re.match('MNNG5', codeInUrl[0])) and re.match('\(', prereq)):
				prereq = "(MINENS5059 || MINEUS8059 || MINESS5040)"
			elif (codeInUrl[0] == "MINE8640"):
				prereq = "(MINEMS5059 || MINETS8059 || MINEIS8058 || MINEJS8335 || MINERS5335)"
			elif (codeInUrl[0] == "MINE8660"):
				prereq = "(MINEMS5059)"
			elif (codeInUrl[0] == "MINE8680" or codeInUrl[0] == "MINE8690"):
				prereq = "(MINENS5059 || (MINE8140 && MINEMS5059))"
			elif (codeInUrl[0] == "MINE8720"):
				prereq = "(MINEUS8059 || (MINE8140 && (MINEIS8058 || MINEJS8335 || MINERS5335 || MINETS8059)"
			elif (codeInUrl[0] == "MMAN4010"):
				prereq = "(138_UOC && MMAN3000)"
			elif (codeInUrl[0] == "MMAN4410"):
				prereq = "(MMAN2400)"
			elif (codeInUrl[0] == "MMAN9001"):
				prereq = "(18_UOC && (MECHIS8338 || MANFCS8338 || MECHAS8539 || MANFAS8539))"
			elif (codeInUrl[0] == "MMAN9002"):
				prereq = "((42_UOC && MMAN9001 && (MECHIS8338 || MANFCS8338 || MECHAS8539 || MANFAS8539))"
			elif (codeInUrl[0] == "MMAN9012" or codeInUrl[0] == "MMAN9024"):
				prereq = "(65_WAM)"

			#MNGT skipped
			#MODL skipped	
			elif (codeInUrl[0] == "MTRN3500"):
				prereq = "(MTRN2500)"
			elif (codeInUrl[0] == "MTRN4110"):
				prereq = "((ELEC1111 || ELEC1112) && MTRN2500 && MMAN3200)"
			elif (codeInUrl[0] == "MTRN4230"):
				prereq = "((MMAN2300 || MMAN3300) && (MTRN2500 && MTRN3020))"
			#MUSC skipped
			elif (codeInUrl[0] == "NANO3002"):
				prereq = "(NANO2002 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "NEUR2201"):
				prereq = "(12_UOC_BABS_BIOS || 12_UOC_PSYC)"
			elif (codeInUrl[0] == "OPTM4211" or codeInUrl[0] == "OPTM4271"):
				prereq = "(OPTM4110 && OPTM4131 && OPTM4151)"
			elif (codeInUrl[0] == "OPTM7203"):
				prereq = "(OPTM7103 && (8760 || 5665 || 7435))"
			elif (codeInUrl[0] == "OPTM7205"):
				prereq = "(OPTM7104 && (8760 || 5665 || 7435 || 5523))"
			elif (codeInUrl[0] == "OPTM7213"):
				prereq = "(7436)"
			elif (codeInUrl[0] == "OPTM7301"):
				prereq = "(OPTM7309 && (8760 || 5665 || 7435))"
			elif (codeInUrl[0] == "PATH2201"):
				prereq = "(ANAT2241 && (ANAT2111 || ANAT1521 || PHSL2101 || BIOC2101 || BIOC2181))"
			elif (codeInUrl[0] == "PATH3207"):
				prereq = "((PATH2201 || PATH2202) && (ANAT2111 || ANAT2511 || ANAT1521 || ANAT1551))"
			elif (codeInUrl[0] == "PHAR2011"):
				prereq = "(6_UOC_LEVEL_1_BABS_BIOS && (3992 || PHSL2101) && 12_UOC_LEVEL_1_CHEM && 6_UOC_LEVEL_1_MATH)"
			elif (codeInUrl[0] == "PHAR3101" or codeInUrl[0] == "PHAR3102" or codeInUrl[0] == "PHAR3251"):
				prereq = "(PHAR2011 || PHAR2211)"
			elif (re.match('PHCM9', codeInUrl[0]) and re.match('\(Student', prereq)):
				prereq = "(MEDICINE_POSTGRADUATE)"
			elif (codeInUrl[0] == "PHSL2101"):
				prereq = "(6_UOC_LEVEL_1_BABS_BIOS && 6_UOC_LEVEL_1_CHEM && 6_UOC_LEVEL_1_MATH)"
			elif (codeInUrl[0] == "PHSL2201"):
				prereq = "(PHSL2101)"
			elif (codeInUrl[0] == "PHSL2501"):
				prereq = "(BABS1201 && CHEM1831 && MATH1041)"
			elif (codeInUrl[0] == "PHSL3211" or codeInUrl[0] == "PHSL3221"):
				prereq = "((PHSL2101 || PHSL2121 || PHSL2501) && (PHSL2201 || PHSL2221 || PHSL2502))"
			elif (codeInUrl[0] == "PHYS1231"):
				prereq = "(PHYS1131 || PHYS1141 || PHYS1121{65})"
			elif (codeInUrl[0] == "PHYS2010" or codeInUrl[0] == "PHYS2040" or codeInUrl[0] == "PHYS2050"):
				prereq = "((PHYS1002 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "PHYS2020"):
				prereq = "((PHYS1002 || PHYS1022 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1021 || MATH1231 || MATH1241 || MATH1031))"
			elif (codeInUrl[0] == "PHYS2030"):
				prereq = "((PHYS1002 || PHYS1022 || PHYS1111 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1021 || MATH1131 || MATH1141 || MATH1031))"
			elif (codeInUrl[0] == "PHYS2060"):
				prereq = "((PHYS1002 || PHYS1022 || PHYS1111 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1021 || MATH1131 || MATH1141 || MATH1031))"
			elif (codeInUrl[0] == "PHYS2110" or codeInUrl[0] == "PHYS2120"):
				prereq = "((PHYS1221 || PHYS1231 || PHYS1241) && (MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "PHYS2210"):
				prereq = "((PHYS1221 || PHYS1231 || PHYS1241) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3011"):
				prereq = "(((PHYS2040 && PHYS2050) || (PHYS2110 && PHYS2210)) && (MATH2221 || MATH2121) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3021"):
				prereq = "(((PHYS2040 && PHYS2060) || (PHYS2110 && PHYS2210)) && (MATH2221 || MATH2121) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3050"):
				prereq = "(PHYS3010 || PHYS3210{CR})"
			elif (codeInUrl[0] == "PHYS3230"):
				#???
				prereq = "((PHYS2011 || PHYS2050 || PHYS2939) && (MATH2011 || MATH2111) && (MATH2120 || MATH2130))"
			elif (codeInUrl[0] == "PHYS3410"):
				#???
				prereq = "(PHYS2210 || (PHYS2060 && PHYS2410))"
			elif (codeInUrl[0] == "PHYS3410"):
				prereq = "((PHYS2120 || PHYS2010) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3550"):
				prereq = "((PHYS1002 || PHYS1231 || PHYS1241 || PHYS1221) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS4949"):
				prereq = "((PHYS3010 || PHYS3080) && 3644)"

			#POLS5100 skipped	
			elif (codeInUrl[0] == "PSYC2001"):
				prereq = "(PSYC1001 && PSYC1011 && PSYC1111)"
			elif (codeInUrl[0] == "PSYC3331"):
				prereq = "((PSYC2001 || PSYC2061 || PSYC2101) || (HESC3504 && 3871))"
			elif (re.match('PSYC7', codeInUrl[0]) and re.match('\(Restricted', prereq)):
				prereq = re.sub(r'^\([A-Za-z ]+', '(', prereq, flags=re.IGNORECASE)
				prereq = re.sub(r'&&', '||', prereq)
			elif (codeInUrl[0] == "PTRL7011"):
				prereq = "(36_UOC)"
			elif (re.match('RISK', codeInUrl[0])):
				prereq = re.sub(r' of Actuarial Studies', '', prereq, flags=re.IGNORECASE)
				prereq = re.sub(r'Program ', '', prereq, flags=re.IGNORECASE)
			elif (codeInUrl[0] == "SAED4403"):
				prereq = "(SAED3404)"
			elif (codeInUrl[0] == "SAED4491"):
				prereq = "(SAED2401 && SAED2406 && SAED3491 && SAED3402 && SAED3404 && SAED3407)"

			elif (codeInUrl[0] == "SAHT4213"):
				prereq = "(SAHT4211)"

			#SART4043 skipped
			elif (codeInUrl[0] == "SART9738"):
				prereq = "(SART9732)"
			elif (codeInUrl[0] == "SCOM3021"):
				#!!!
				prereq = "((SCOM1021 || SCOM2014) && SCOM2021)"
			elif (codeInUrl[0] == "SENG1031"):
				#???
				prereq = "(SOFTWARE_ENGINEERING_PROGRAM || BIOINFOMATICS_PROGRAM)"
			elif (codeInUrl[0] == "SENG2021"):
				prereq = "((SENG2011 || COMP2911) && SOFTWARE_ENGINEERING_PROGRAM)"
			elif (codeInUrl[0] == "SENG4904"):
				prereq = "(SOFTWARE_ENGINEERING_CO_OP_PROGRAM)"
			elif (codeInUrl[0] == "SENG4910"):
				prereq = "(126_UOC_SENGA1)"
			elif (codeInUrl[0] == "SENG4921"):
				prereq = "(SOFTWARE_ENGINEERING_PROGRAM)"
			#SOCF5101 skiiped
			#SOCW skipped
			#SOMA4045 skipped
			elif (codeInUrl[0] == "SOMA9718"):
				prereq = "(SOMA9717)"
			elif (codeInUrl[0] == "SRAP3002"):
				prereq = "((SRAP2002 && SRAP3000 && SRAP3001) || (SLSP2002 && SLSP3000 && SLSP3001) || (SRAP2001 && SRAP2002) || (SLSP2001 && SLSP2002))"
			elif (codeInUrl[0] == "SRAP3006"):
				prereq = "(SRAP1000 && SRAP1001 && SRAP2001 && SRAP2002 && DIPP1112 && SOCIAL_RESEARCH_AND_POLICY_PROGRAM)"
			elif (re.match('SRAP405', codeInUrl[0])):
				prereq = "(SOCIAL_RESEARCH_AND_POLICY_PROGRAM)"
			#SRAP5 skipped
			elif (codeInUrl[0] == "TABL1710"):
				prereq = "(!(4733 || 4737 || 4744))"
			elif (codeInUrl[0] == "TABL2712" or codeInUrl[0] == "TABL2731" or codeInUrl[0] == "TABL2732" or codeInUrl[0] == "TABL3761" or codeInUrl[0] == "TABL3771" or codeInUrl[0] == "TABL3791"):
				prereq = "(LEGT1710 || TABL1710 || 12_UOC_BUSINESS)"
			elif (codeInUrl[0] == "TABL2741"):
				prereq = "((LEGT1710 || TABL1710) && (!(4733 || 4737 || 4744)))"
			elif (codeInUrl[0] == "TABL3003" or codeInUrl[0] == "TABL3005" or codeInUrl[0] == "TABL3006" or codeInUrl[0] == "TABL3007" or codeInUrl[0] == "TABL3015" or codeInUrl[0] == "TABL3016" or codeInUrl[0] == "TABL3020" or codeInUrl[0] == "TABL3022" or codeInUrl[0] == "TABL3025" or codeInUrl[0] == "TABL3028" or codeInUrl[0] == "TABL3031" or codeInUrl[0] == "TABL3040" or codeInUrl[0] == "TABL3044"):
				prereq = "(48_UOC)"
			elif (codeInUrl[0] == "TABL3010" or codeInUrl[0] == "TABL3026"):
				prereq = "(TABL2751 || LEGT2751 || 48_UOC_4620)"
			elif (codeInUrl[0] == "TABL5512"):
				prereq = "(8409 || 8415)"
			elif (codeInUrl[0] == "TABL5517"):
				prereq = "(TABL5511 || LEGT5511 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "TABL5541" or codeInUrl[0] == "TABL5551"):
				#also a coreq
				prereq = "(LEGT5511 || TABL5511 || LEGT5512 || TABL5512)"
			elif (codeInUrl[0] == "ZBUS2902" or codeInUrl[0] == "ZBUS3901" or codeInUrl[0] == "ZBUS3902"):
				prereq = "(4462 && SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ZEIT2307" or codeInUrl[0] == "ZGEN2222"):
				prereq = "(36_UOC_LEVEL_1)"
			elif (codeInUrl[0] == "ZEIT2904"):
				prereq = "(ZEIT2903 && 4469)"
			elif (codeInUrl[0] == "ZEIT3903"):
				prereq = "(96_UOC && 4469)"
			elif (codeInUrl[0] == "ZEIT3904"):
				prereq = "(ZEIT3903 && 4469)"
			elif (codeInUrl[0] == "ZEIT4003"):
				#???
				prereq = "(ZEIT2500 && (ZEIT2503 || ZEIT2602))"
			elif (codeInUrl[0] == "ZEIT4902"):
				prereq = "(CDF_PROGRAM)"
			elif (codeInUrl[0] == "ZHSS2427"):
				prereq = "((ZHSS1201 && ZHSS1202) || (ZHSS1401 && ZHSS1402))"
			elif (codeInUrl[0] == "ZHSS2506" or codeInUrl[0] == "ZHSS3501" or codeInUrl[0] == "ZHSS3505"):
				prereq = "((ZHSS1102 && ZHSS1202) || (ZHSS1102 && ZHSS1302) || (ZHSS1102 && ZHSS1304) || (ZHSS1102 && ZHSS1402) || (ZHSS1102 && ZPEM1202) || (ZHSS1202 && ZHSS1302) || (ZHSS1202 && ZHSS1304) || (ZHSS1202 && ZHSS1402) ||(ZHSS1202 && ZPEM1202) || (ZHSS1302 && ZHSS1304) || (ZHSS1302 && ZHSS1402) || (ZHSS1302 && ZPEM1202) || (ZHSS1304 && ZPEM1202) || (ZHSS1402 && ZPEM1202)"
			elif (codeInUrl[0] == "ZHSS2600"):
				#!!!
				prereq = "(SCHOOL_APPROVAL)"
			#ZHSS3201 skipped
			#ZHSS3202 skipped
			elif (codeInUrl[0] == "ZHSS3231"):
				prereq = "(ZHSS1201 || ZHSS1202 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ZHSS3234"):
				prereq = "(ZHSS1201 || ZHSS1202 || (ZHSS1401 && ZHSS1402))"
			elif (codeInUrl[0] == "ZHSS3421"):
				prereq = "((ZHSS1401 || ZHSS1402 || ZHSS2600)"
			elif (codeInUrl[0] == "ZPEM2401"):
				#???
				prereq = "((ZPEM1302 || ZPEM1304) && ZPEM2302 && ZPEM1501 && ZPEM1402)"
			elif (codeInUrl[0] == "ZPEM2502"):
				prereq = "((ZPEM1301 || ZPEM1303) && (ZPEM1302 || ZPEM1304) && ZPEM1501 && ZPEM1502 && (ZPEM2302 || ZPEM2309))"
			elif (codeInUrl[0] == "ZPEM2506"):
				prereq = "((ZPEM1301 || ZPEM1303) && (ZPEM1302 || ZPEM1304) && ZPEM1501 && (ZPEM1402 || ZPEM1502))"
			elif (codeInUrl[0] == "ZPEM2509"):
				prereq = "((ZPEM1301 || ZPEM1303) && (ZPEM1302 || ZPEM1304) && ZPEM1501 && ZPEM1502)"
			elif (codeInUrl[0] == "ZPEM3103"):
				prereq = "(ZPEM1301 && ZPEM1302 && (ZPEM2113 || ZPEM2502))"
			elif (codeInUrl[0] == "ZPEM3107"):
				prereq = "((ZPEM2102 && ZPEM2113) || ZINT2501)"
			elif (codeInUrl[0] == "ZPEM3524"):
				prereq = "((ZPEM2401 || ZPEM2502) && ZPEM2506)"


			#php explosion preparation
			prereq = re.sub(r'\(', '( ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\)', ' )', prereq, flags=re.IGNORECASE)

			

			f.write("INSERT INTO pre_reqs (course_code, title, uoc, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, prereqCondition, prereq, codeInUrl[0], career))
         
			#print prereq[0].group()
		else:
			#print "went here"
			f.write("INSERT INTO pre_reqs (course_code, title, uoc, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))

		if (courseCode2 or coreq):

			if (courseCode2):
				#print courseCode2[0][0]
				#print "searching"
				#print coreqOnlySentence.search(courseCode2[0][0]).group(0)
				#print "groups"

				coreq = coreqOnlySentence.search(courseCode2[0][0]).group(0)
			#print hc
			#print "printing"
			#print coreq
				
			
			coreqCondition = coreq
			coreq = re.sub(r"\'", "\'\'", coreq, flags=re.IGNORECASE)
			coreqCondition = re.sub(r"\'", "\'\'", coreqCondition, flags=re.IGNORECASE)

			#remove coreq word
			coreq = re.sub(r"Co(.*?:|requisite) ?", "(", coreq, flags=re.IGNORECASE)

			#change to ands
			coreq = re.sub(r"\sAND\s", " && ", coreq, flags=re.IGNORECASE)
			coreq = re.sub(r"\s&\s", " && ", coreq, flags=re.IGNORECASE)
			coreq = re.sub(r"\sincluding\s", " && ", coreq, flags=re.IGNORECASE)

			#comma can mean and/or
			coreq = re.sub(r",\s*or", " || ", coreq, flags=re.IGNORECASE)
			coreq = re.sub(r",", " && ", coreq, flags=re.IGNORECASE)

			#change to ors
			coreq = re.sub(r';', ' || ', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\sOR\s', ' || ', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\/', ' || ', coreq, flags=re.IGNORECASE)

			#change to uoc
			coreq = re.sub(r'\s*uoc\b', '_UOC', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\s*uc\b', '_UOC', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', coreq, flags=re.IGNORECASE)

			#remove unnecessary words
			coreq = re.sub(r'\.', '', coreq, flags=re.IGNORECASE)

			#change [] to ()
			coreq = re.sub(r'\[', '(', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\]', ')', coreq, flags=re.IGNORECASE)

			coreq = re.sub(r'stream', '', coreq, flags=re.IGNORECASE)

			#cleanup
			coreq = re.sub(r'&&\s*&&$', '&&', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'&&\s*$', ' ', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\|\|\s*$', ' ', coreq, flags=re.IGNORECASE)
			coreq += ")"
			coreq = re.sub(r'\(\s*\)', ' ', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\(\s*', '(', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\s*\)', ')', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\s\s+', ' ', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\'', '\'\'', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'^\s+$', '', coreq, flags=re.IGNORECASE)

			#print "writing"

			#manual
			if (codeInUrl[0] == "ACTL4002"):
				coreq = "(ACTL3162 && ACTL3182)"
			elif (codeInUrl[0] == "ACTL4303"):
				coreq = ""
			elif (codeInUrl[0] == "ACTL5303"):
				coreq = "(ACTL5109)"
			elif (codeInUrl[0] == "AERO3640"):
				coreq = "(MMAN3200)"
			elif (codeInUrl[0] == "COMP3231"):
				coreq = "(COMP2121 && 75_WAM)"
			elif (codeInUrl[0] == "COMP3891"):
				coreq = "((COMP2121 || ELEC2142) && 75_WAM)"
			elif (codeInUrl[0] == "COMP4128" and career == "UG"):
				coreq = "(COMP3821 || (COMP3121 && 75_WAM)"
			elif (codeInUrl[0] == "ELEC2134"):
				coreq = "(ELEC1111 || ELEC1112)"
			elif (codeInUrl[0] == "ENGG0380"):
				coreq = "(ENGINEERING_PROGRAM)"
			elif (codeInUrl[0] == "FINS5512"):
				coreq = "(ACCT5906 || ECON5103 || 9273 || 5273 || 7273 || 8007)"
			elif (codeInUrl[0] == "FINS5516" or codeInUrl[0] == "FINS5530" or codeInUrl[0] == "FINS5531" or codeInUrl[0] == "FINS5542"):
				coreq = "((FINS5513 || 8406)"
			elif (codeInUrl[0] == "FINS5522"):
				coreq = "((FINS5512 && FINS5513) || 8406)"
			elif (codeInUrl[0] == "FINS5533"):
				coreq = "(FINS5513 || FINS5561 || 8406)"
			elif (codeInUrl[0] == "FINS5566"):
				coreq = "(FINS5512 || 8406 || 8413)"
			elif (codeInUrl[0] == "FOOD9430"):
				coreq = "(24_UOC_LEVEL_3_4_FOOD)"
			#JURD skipped
			#LAWS skipped
			elif (codeInUrl[0] == "MARK5820"):
				coreq = "(MARK5800 || MARK5801 || (7291 || 5291 || 8291 || 8281))"
			elif (codeInUrl[0] == "MATS5003"):
				coreq = "(MATS5003)"
			elif (codeInUrl[0] == "MGMT5603" or codeInUrl[0] == "MGMT5604"):
				coreq = "(IBUS5601 || MGMT5601)"
			#MNGT6274 skipped
			#MNGT6372 skipped
			elif (codeInUrl[0] == "OPTM4271"):
				coreq = "(OPTM4211 && OPTM4231 && OPTM4251)"
			elif (codeInUrl[0] == "PHYS3080"):
				coreq = "((PHYS3010 || PHYS3210) && PHYS3020)"
			#ZHSS3201 skipped
			#ZHSS3202 skipped

			#php explosion preparation
			coreq = re.sub(r'\(', '( ', coreq, flags=re.IGNORECASE)
			coreq = re.sub(r'\)', ' )', coreq, flags=re.IGNORECASE)



			g.write("INSERT INTO co_reqs (course_code, title, uoc, career, co_req_conditions, norm_co_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, coreqCondition, coreq, codeInUrl[0], career))
         
			#print prereq[0].group()
		else:
			#print "went here"
			g.write("INSERT INTO co_reqs (course_code, title, uoc, career, co_req_conditions, norm_co_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))

		if equivalence:
			#print equivalence
			equivalenceCondition = equivalence
			equivalence = re.sub(r"\'", "\'\'", equivalence, flags=re.IGNORECASE)
			equivalenceCondition = re.sub(r"\'", "\'\'", equivalenceCondition, flags=re.IGNORECASE)

			#remove equivalence word
			equivalence = re.sub(r"[eE]quiv.*?:", "(", equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r"<\/strong>&nbsp;", " ", equivalence, flags=re.IGNORECASE)

			#change to ands
			equivalence = re.sub(r"\sAND\s", " || ", equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r"\s&\s", " || ", equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r"\sincluding\s", " || ", equivalence, flags=re.IGNORECASE)

			#comma can mean and/or
			equivalence = re.sub(r",\s*or", " || ", equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r",", " || ", equivalence, flags=re.IGNORECASE)

			#change to ors
			equivalence = re.sub(r';', ' || ', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\sOR\s', ' || ', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\/', ' || ', equivalence, flags=re.IGNORECASE)

			#change to uoc
			equivalence = re.sub(r'\s*uoc\b', '_UOC', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\s*uc\b', '_UOC', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', equivalence, flags=re.IGNORECASE)

			#remove unnecessary words
			equivalence = re.sub(r'\.', '', equivalence, flags=re.IGNORECASE)

			#change [] to ()
			equivalence = re.sub(r'\[', '(', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\]', ')', equivalence, flags=re.IGNORECASE)



			equivalence = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'approval from the School', "SCHOOL_APPROVAL", equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'school approval', "SCHOOL_APPROVAL", equivalence, flags=re.IGNORECASE)
			#equivalence = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'Enrolment in program ([0-9]+)', r'\1', equivalence, flags=re.IGNORECASE)

			#equivalence = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', 
				#"(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", equivalence, flags=re.IGNORECASE)
			#equivalence = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s*[^a-zA-Z0-9]*$', r'\1_UOC_LEVEL_\2', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s+([A-Za-z])', r'\1_UOC_LEVEL_\2_\3', equivalence, flags=re.IGNORECASE)

			equivalence = re.sub(r'stream', '', equivalence, flags=re.IGNORECASE)

			#cleanup
			equivalence = re.sub(r'&&\s*&&$', '&&', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'&&\s*$', ' ', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\|\|\s*$', ' ', equivalence, flags=re.IGNORECASE)
			equivalence += ")"
			equivalence = re.sub(r'\(\s*\)', ' ', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\(\s*', '(', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\s*\)', ')', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\s\s+', ' ', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\'', '\'\'', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'^\s+$', '', equivalence, flags=re.IGNORECASE)

			#php explosion preparation
			equivalence = re.sub(r'\(', '( ', equivalence, flags=re.IGNORECASE)
			equivalence = re.sub(r'\)', ' )', equivalence, flags=re.IGNORECASE)

			#print equivalence
			

			h.write("INSERT INTO equivalence (course_code, title, uoc, career, equivalence_conditions, norm_equivalence_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM equivalence WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, equivalenceCondition, equivalence, codeInUrl[0], career))
         
			#print prereq[0].group()
		else:
			#print "went here"
			h.write("INSERT INTO equivalence (course_code, title, uoc, career, equivalence_conditions, norm_equivalence_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM equivalence WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))

		if exclusion:
			#print exclusion
			exclusionCondition = exclusion
			exclusion = re.sub(r"\'", "\'\'", exclusion, flags=re.IGNORECASE)
			exclusionCondition = re.sub(r"\'", "\'\'", exclusionCondition, flags=re.IGNORECASE)

			#remove exclusion word
			exclusion = re.sub(r"[eE]xcl.*?:", "(", exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r"<\/strong>&nbsp;", " ", exclusion, flags=re.IGNORECASE)

			#change to ands
			exclusion = re.sub(r"\sAND\s", " || ", exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r"\s&\s", " || ", exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r"\sincluding\s", " || ", exclusion, flags=re.IGNORECASE)

			#comma can mean and/or
			exclusion = re.sub(r",\s*or", " || ", exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r",", " || ", exclusion, flags=re.IGNORECASE)

			#change to ors
			exclusion = re.sub(r';', ' || ', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\sOR\s', ' || ', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\/', ' || ', exclusion, flags=re.IGNORECASE)

			#change to uoc
			exclusion = re.sub(r'\s*uoc\b', '_UOC', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\s*uc\b', '_UOC', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', exclusion, flags=re.IGNORECASE)

			#remove unnecessary words
			exclusion = re.sub(r'\.', '', exclusion, flags=re.IGNORECASE)

			#change [] to ()
			exclusion = re.sub(r'\[', '(', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\]', ')', exclusion, flags=re.IGNORECASE)



			exclusion = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'approval from the School', "SCHOOL_APPROVAL", exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'school approval', "SCHOOL_APPROVAL", exclusion, flags=re.IGNORECASE)
			#exclusion = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'Enrolment in program ([0-9]+)', r'\1', exclusion, flags=re.IGNORECASE)

			#exclusion = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', 
				#"(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", exclusion, flags=re.IGNORECASE)
			#exclusion = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s*[^a-zA-Z0-9]*$', r'\1_UOC_LEVEL_\2', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s+([A-Za-z])', r'\1_UOC_LEVEL_\2_\3', exclusion, flags=re.IGNORECASE)

			exclusion = re.sub(r'stream', '', exclusion, flags=re.IGNORECASE)

			#cleanup
			exclusion = re.sub(r'&&\s*&&$', '&&', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'&&\s*$', ' ', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\|\|\s*$', ' ', exclusion, flags=re.IGNORECASE)
			exclusion += ")"
			exclusion = re.sub(r'\(\s*\)', ' ', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\(\s*', '(', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\s*\)', ')', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\s\s+', ' ', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\'', '\'\'', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'^\s+$', '', exclusion, flags=re.IGNORECASE)

			

			#print exclusion
			if (codeInUrl[0] == "ARTS1480"):
				exclusion = "(FREN1000 || FREN1101 || GENT0425)"
			elif (codeInUrl[0] == "ARTS1481"):
				exclusion = "(FREN1002 || FREN1102)"
			elif (codeInUrl[0] == "ARTS2480" or codeInUrl[0] == "ARTS2481"):
				exclusion = ""
			elif (codeInUrl[0] == "BIOM2451"):
				exclusion = "(ENGINEERING_PROGRAM)"
			elif (codeInUrl[0] == "CEIC4001" or codeInUrl[0] == "CEIC4002" or codeInUrl[0] == "CHEN6710"):
				exclusion = "(CEIC4005)"
			elif (codeInUrl[0] == "CEIC4003"):
				exclusion = "(CEIC4005 || CEIC4006)"
			elif (codeInUrl[0] == "CEIC4005"):
				exclusion = "(CEIC4002 || CEIC4003)"
			elif (codeInUrl[0] == "CEIC4006"):
				exclusion = "(CEIC4003 || CEIC4005)"
			elif (codeInUrl[0] == "COMP4931"):
				exclusion = "(4515)"
			elif (codeInUrl[0] == "CRIM2020"):
				exclusion = "(LAW_PROGRAM)"
			elif (codeInUrl[0] == "ECON1203"):
				exclusion = "(MATH2841 || MATH2801 || MATH2901 || MATH2099 || ACTL2002)"
			elif (re.match('GENC', codeInUrl[0])):
				exclusion = "(BUSINESS_PROGRAM)"
			elif (re.match('GENM', codeInUrl[0])):
				exclusion = "(MEDICINE_PROGRAM)"
			elif (re.match('GENT', codeInUrl[0])):
				exclusion = "(ARTS_SOCIAL_SCIENCES_PROGRAM)"
			elif (codeInUrl[0] == "IEST6907"):
				exclusion = "(3988 || 3932)"
			elif (codeInUrl[0] == "JURD7321"):
				exclusion = "(JURD7446 || JURD7448 || JURD7617)"
			elif (codeInUrl[0] == "JURD7446" or codeInUrl[0] == "JURD7448"):
				exclusion = "(JURD7321 || JURD7617)"
			elif (codeInUrl[0] == "JURD7617"):
				exclusion = "(JURD7321)"
			elif (codeInUrl[0] == "MATH2089"):
				exclusion = "(CVEN2002 || CVEN2025 || CVEN2702 || ECON3209 || MATH2049 || MATH2829 || MATH2839 || MATH2899 || MINE2700)"
			elif (codeInUrl[0] == "MATH2301"):
				exclusion = "(MATH2089 || CVEN2002 || CVEN2702)"
			elif (codeInUrl[0] == "MSCI0501"):
				exclusion = "(GENS4625 || MSCI2001 || GENB5001 || SCIENCE_PROGRAM)"
			elif (codeInUrl[0] == "PHYS4979"):
				exclusion = "(PHYS3780 || !(3644))"

			#php explosion preparation
			exclusion = re.sub(r'\(', '( ', exclusion, flags=re.IGNORECASE)
			exclusion = re.sub(r'\)', ' )', exclusion, flags=re.IGNORECASE)


			

			i.write("INSERT INTO exclusion (course_code, title, uoc, career, exclusion_conditions, norm_exclusion_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM exclusion WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, exclusionCondition, exclusion, codeInUrl[0], career))
         
			#print prereq[0].group()
		else:
			#print "went here"
			i.write("INSERT INTO exclusion (course_code, title, uoc, career, exclusion_conditions, norm_exclusion_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM exclusion WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))


	except:
		print title
		print uoc
		print codeInUrl,
		print "No Handbook Entry"
		#prereq = "WARNING"
		#coreq = "WARNING"
		#equivalence = "WARNING"
		#if prereqCondition:
		#	f.write("INSERT INTO pre_reqs (course_code, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, prereqCondition, prereq, codeInUrl[0], career))
		#if coreqCondition:
	#		g.write("INSERT INTO co_reqs (course_code, career, co_req_conditions, norm_co_req_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, coreqCondition, coreq, codeInUrl[0], career))
		#if equivalenceCondition:
		#	h.write("INSERT INTO equivalence (course_code, career, equivalence_conditions, norm_equivalence_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, equivalenceCondition, equivalence, codeInUrl[0], career))


f.close()
g.close()
h.close()
i.close()