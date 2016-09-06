#!/usr/bin/python

#need course title and uoc

import urllib2
import re
from datetime import date
#from bs4 import BeautifulSoup

#find how many courses left to go
#select count(*)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'CVEN1000';
#find the courses
#select course_code, career, left(pre_req_conditions,120) as c, left(norm_pre_req_conditions,120)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'CVEN1000';
#select course_code, career, left(pre_req_conditions,90) as c, left(norm_pre_req_conditions,90)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'MARK1000';

#find a specific course to show the full length conditions
#select * from pre_reqs where course_code like '%COMP4930%';

#select course_code, career, left(pre_req_conditions,90) as c, left(norm_pre_req_conditions,85)  from pre_reqs  where course_code > 'OPTM7000' order by course_code;

year = date.today().year
allCourseCode = set()
code_pattern = re.compile(r"[A-Z]{4}[0-9]{4}")
pre_req_tag = re.compile(r"<p>[pP]re.*?<\/p>")
pre_req_sentence = re.compile(r"<p>([pP]re.*?)([pP]requisite)?([cC]o-?[Rr]eq.*?)?([Ee]xcl.*?)?<\/p>")
co_req_tag = re.compile(r"([cC]o[ \-][rR]eq.*?)(\.|<|Excl|Equi)")
co_req_sentence = re.compile(r"([cC]o[ \-][rR]eq.*)")
equivalence_tag = re.compile(r"<p>.*?([Ee]quivalent:.*?)<\/p>")
equivalence_sentence = re.compile(r".*?([Ee]quivalent:.*)")
exclusion_tag = re.compile(r"<p>.*?([Ee]xclu.*?:.*?)<\/p>")
exclusion_sentence = re.compile(r".*?([Ee]xclu.*?:.*)")
uoc_pattern = re.compile(r"<p><strong>Units of Credit:<\/strong>.*?([0-9]+)<\/p>")
title_pattern = re.compile(r"<title>.*?-\s*(.*?)\s*- [A-Z]{4}[0-9]{4}<\/title>",  re.DOTALL)
subject_area_sentence = re.compile(r"<u>Subject Areas?<\/u>: <i>(.*?)<\/i>")

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

j = open("subject_area.sql", "w")
j.write("DROP TABLE IF EXISTS subject_area;\n")
j.write("CREATE TABLE subject_area (course_code text, title text, uoc integer, career text, area text);\n")


undergraduate_url = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
undergraduate_html = urllib2.urlopen(undergraduate_url).read()
subject_code_website_pattern = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/" + str(year) + "/[A-Z]{4}[0-9]{4}\.html")
subject_code_websites = re.findall(subject_code_website_pattern, undergraduate_html)

postgraduate_url = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
postgraduate_html = urllib2.urlopen(postgraduate_url).read()
subject_code_website_pattern = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/" + str(year) + "/[A-Z]{4}[0-9]{4}\.html")
subject_code_websites += re.findall(subject_code_website_pattern, postgraduate_html)

research_url = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Research&descr=All"
research_html = urllib2.urlopen(research_url).read()
subject_code_website_pattern = re.compile(r"http://www.handbook.unsw.edu.au/research/courses/" + str(year) + "/[A-Z]{4}[0-9]{4}\.html")
subject_code_websites += re.findall(subject_code_website_pattern, research_html)

undergraduate_url = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
undergraduate_html = urllib2.urlopen(undergraduate_url).read()
subject_code_website_pattern = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/" + str(year - 1) + "/[A-Z]{4}[0-9]{4}\.html")
subject_code_websites += re.findall(subject_code_website_pattern, undergraduate_html)

postgraduate_url = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
postgraduate_html = urllib2.urlopen(postgraduate_url).read()
subject_code_website_pattern = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/" + str(year - 1) + "/[A-Z]{4}[0-9]{4}\.html")
subject_code_websites += re.findall(subject_code_website_pattern, postgraduate_html)

research_url = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Research&descr=All"
research_html = urllib2.urlopen(research_url).read()
subject_code_website_pattern = re.compile(r"http://www.handbook.unsw.edu.au/research/courses/" + str(year - 1) + "/[A-Z]{4}[0-9]{4}\.html")
subject_code_websites += re.findall(subject_code_website_pattern, research_html)


for subject_code_website in subject_code_websites:
	career = "UG"
	if "postgraduate" in subject_code_website:
		career = "PG"
	if "research" in subject_code_website:
		career = "RS"

	#print subject_code_website
	normalised_pre_req = ""
	original_pre_req_condition = ""
	normalised_co_req = ""
	original_co_req_condition = ""
	normalised_equivalence = ""
	original_equivalence_condition = ""
	normalised_exclusion = ""
	original_exclusion_condition = ""
	uoc = ""
	title = ""
	subject_area = ""

	try:
		codeInUrl = re.findall(code_pattern, subject_code_website)
		subject_code_content = urllib2.urlopen(subject_code_website).read()
		
		#print "debug"
		pre_req_matches = re.findall(pre_req_tag, subject_code_content)
		co_req_matches = re.findall(co_req_tag, subject_code_content)
		equivalence_matches = re.findall(equivalence_tag, subject_code_content)
		exclusion_matches = re.findall(exclusion_tag, subject_code_content)
		subject_area_matches = re.findall(subject_area_sentence, subject_code_content)
		uoc = int(uoc_pattern.search(subject_code_content).group(1))
		title = title_pattern.search(subject_code_content).group(1)
		title = re.sub(r"\'", "\'\'", title, flags=re.IGNORECASE)

		if equivalence_matches:
			#print "trying"
			normalised_equivalence = equivalence_sentence.search(equivalence_matches[0]).group(0)

		if exclusion_matches:
			normalised_exclusion = exclusion_sentence.search(exclusion_matches[0]).group(0)

		if subject_area_matches:
			subject_area = subject_area_matches[0]

		if pre_req_matches:
			normalised_pre_req = pre_req_sentence.search(pre_req_matches[0]).group(1)
			normalised_co_req = pre_req_sentence.search(pre_req_matches[0]).group(3)

			original_pre_req_condition = normalised_pre_req
			normalised_pre_req = re.sub(r"\'", "\'\'", normalised_pre_req, flags=re.IGNORECASE)
			original_pre_req_condition = re.sub(r"\'", "\'\'", original_pre_req_condition, flags=re.IGNORECASE)

			#remove normalised_pre_req word
			normalised_pre_req = re.sub(r"Pre(.*?:|requisite)", "(", normalised_pre_req, flags=re.IGNORECASE)

			#change to ands
			normalised_pre_req = re.sub(r"\sAND\s", " && ", normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r"\s&\s", " && ", normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r"\sincluding\s", " && ", normalised_pre_req, flags=re.IGNORECASE)

			#comma can mean and/or
			normalised_pre_req = re.sub(r",\s*or", " || ", normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r",", " && ", normalised_pre_req, flags=re.IGNORECASE)

			#change to ors
			normalised_pre_req = re.sub(r';', ' || ', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\sOR\s', ' || ', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\/', ' || ', normalised_pre_req, flags=re.IGNORECASE)

			#change to uoc
			normalised_pre_req = re.sub(r'\s*uoc\b', '_UOC', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\s*uc\b', '_UOC', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', normalised_pre_req, flags=re.IGNORECASE)

			#remove unnecessary words
			normalised_pre_req = re.sub(r'\.', '', normalised_pre_req, flags=re.IGNORECASE)

			#change [] to ()
			normalised_pre_req = re.sub(r'\[', '(', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\]', ')', normalised_pre_req, flags=re.IGNORECASE)



			normalised_pre_req = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'approval from the School', "SCHOOL_APPROVAL", normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'school approval', "SCHOOL_APPROVAL", normalised_pre_req, flags=re.IGNORECASE)
			#normalised_pre_req = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'Enrolment in program ([0-9]+)', r'\1', normalised_pre_req, flags=re.IGNORECASE)

			#normalised_pre_req = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', 
				#"(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", normalised_pre_req, flags=re.IGNORECASE)
			#normalised_pre_req = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s*[^a-zA-Z0-9]*$', r'\1_UOC_LEVEL_\2', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s+([A-Za-z])', r'\1_UOC_LEVEL_\2_\3', normalised_pre_req, flags=re.IGNORECASE)

			normalised_pre_req = re.sub(r'stream', '', normalised_pre_req, flags=re.IGNORECASE)

			#cleanup
			normalised_pre_req = re.sub(r'&&\s*&&$', '&&', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'&&\s*$', ' ', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\|\|\s*$', ' ', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req += ")"
			normalised_pre_req = re.sub(r'\(\s*\)', ' ', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\(\s*', '(', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\s*\)', ')', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\s\s+', ' ', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\'', '\'\'', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'^\s+$', '', normalised_pre_req, flags=re.IGNORECASE)
			#print normalised_pre_req


			#manual
			if (codeInUrl[0] == "ACCT1501"):
				normalised_pre_req = ""
			if (codeInUrl[0] == "ACCT2507"):
				normalised_pre_req = "(ACCT1511{80})"
			elif (codeInUrl[0] == "ACCT4794" or codeInUrl[0] == "ACCT4809" or codeInUrl[0] == "ACCT4851" or
				codeInUrl[0] == "ACCT4852" or codeInUrl[0] == "ACCT4897"):
				#!!!
				normalised_pre_req = "(HONOURS_MAJOR_ACCOUNTING)"
			elif (codeInUrl[0] == "ACCT5919" or codeInUrl[0] == "ACCT5996"):
				#actually can be a normalised_co_req
				normalised_pre_req = "(ACCT5930 || COMM5003 || ACCT5906)"
			elif (codeInUrl[0] == "ACCT5967"):
				normalised_pre_req = "(ACCT5997)"
			elif (codeInUrl[0] == "ACTL1101"):
				normalised_pre_req = "(MATH1151 && (3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737))"
			elif (codeInUrl[0] == "ACTL2102"):
				normalised_pre_req = "((ACTL2131 || MATH2901) && (3154 || 3155 || 3586 || 3587 || 3588 || 3589 || 4737))"
			elif (codeInUrl[0] == "ACTL3142"):
				normalised_pre_req = "(ACTL2131 && ACTL2111 && MAJOR_ACTUARIAL)"
			elif (codeInUrl[0] == "ACTL3162"):
				normalised_pre_req = "(ACTL2102 || (MATH2901 && (MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)))"
			elif (codeInUrl[0] == "ACTL4000" or codeInUrl[0] == "ACTL4003"):
				#!!!
				normalised_pre_req = "(HONOURS_MAJOR_ACTUARIAL)"
			elif (codeInUrl[0] == "ACTL4002"):
				#changed normalised_pre_req and normalised_co_req
				normalised_pre_req = "(ACTL4001)"
			elif (codeInUrl[0] == "ACTL4303"):
				#SPECIAL CONDITION
				normalised_pre_req = "(ACTL3141 || ACTL4001)"
			elif (codeInUrl[0] == "ACTL5103" or codeInUrl[0] == "ACTL5104" or codeInUrl[0] == "ACTL5106"):
				normalised_pre_req = "(ACTL5101 && (8411 || 8416))"
			elif (codeInUrl[0] == "ACTL5105" or codeInUrl[0] == "ACTL5109"):
				#needs normalised_co_req for 5109
				normalised_pre_req = "(ACTL5101 && ACTL5102 && (8411 || 8416))"
			#ACTL5200 needs normalised_co_req
			elif (codeInUrl[0] == "ACTL5401"):
				normalised_pre_req = "(7273 || 5273 || 9273 || SCHOOL_APPROVAL)"
			#AERO3640 needs normalised_co_req
			elif (codeInUrl[0] == "ANAT2111"):
				normalised_pre_req = "(BABS1201 && (ANAT2241 || BABS1202 || BABS2202 || BABS2204 || BIOC2201 || BIOC2291 || BIOS1101 || HESC1501 || PHSL2101 || PHSL2121 || PHSL2501 || VISN1101))"
			elif (codeInUrl[0] == "ANAT2241"):
				normalised_pre_req = "(BABS1201 && PROGRAM_WAM_55)"
			elif (codeInUrl[0] == "ARCH1201"):
				normalised_pre_req = "((ARCH1101 && ARCH1102) || ARCH1390)"
			elif (codeInUrl[0] ==  "ARCH1302"):
				normalised_pre_req = "(ARCH1202 && ARCH1301)"
			elif (codeInUrl[0] ==  "ARCH1395"):
				normalised_pre_req = "(ARCH1394 && ARCH1384)"
			elif (codeInUrl[0] == "ARTS1006"):
				normalised_pre_req = "(ARTS1005{CR})"
			elif (codeInUrl[0] == "ARTS2006"):
				normalised_pre_req = "(MUSC1704 || MUSC1705 || MUSC1706 || ARTS1005 || 3425 || 3426 || 3427 || 3448 || 3449)"
			elif (codeInUrl[0] == "ARTS2007"):
				normalised_pre_req = "(30_UOC_LEVEL_1 && ARTS1005)"
			elif (codeInUrl[0] == "ARTS2038"):
				#normalised_pre_req = "(30_UOC && 12_UOC_LEVEL_1_ENGLISH)"
				normalised_pre_req = "(30_UOC && 12_UOC_LEVEL_1_ARTS)"
			elif (codeInUrl[0] == "ARTS2050"):
				#!!!
				normalised_pre_req = "(12_UOC_LEVEL_1 && (FACULTY_ARTS))"
			elif (codeInUrl[0] == "ARTS2065"):
				normalised_pre_req = "(30_UOC_LEVEL_1 && (ARTS1060 || ARTS1062))"
			elif (codeInUrl[0] == "ARTS2195"):
				normalised_pre_req = "(30_UOC at Level 1 && (ARTS1190 || ARTS1270 || ARTS1900))"
			elif (codeInUrl[0] == "ARTS2452"):
				#???
				normalised_pre_req = "(ARTS3451 || ARTS3452 || ARTS3453)"
			elif (codeInUrl[0] == "ARTS2690" or codeInUrl[0] == "ARTS2692" or codeInUrl[0] == "ARTS2693" 
				or codeInUrl[0] == "ARTS2694" or codeInUrl[0] == "ARTS2696"):
				#normalised_pre_req = "(30_UOC && 12_UOC_LEVEL_1_LINGUISTICS)"
				normalised_pre_req = "(30_UOC && 12_UOC_LEVEL_1_ARTS)"

			##ARTS[345]### skipped
			elif (codeInUrl[0] == "ATSI3008"):
				#!!!
				normalised_pre_req = "(120_UOC && STREAM_INDIGENOUS && REMAINING_24_UOC)"
			elif (codeInUrl[0] == "AVIA2013"):
				normalised_pre_req = "((AVIA1401 && AVIA1901 && MATH1041) || (AVIA1401 && AVIA1901 && PHYS1211) || (AVIA1401 && MATH1041 && PHYS1211) || (AVIA1901 && MATH1041 && PHYS1211))"
			elif (codeInUrl[0] == "AVIA3101"):
				normalised_pre_req = "(AVIA1900 || AVIA2004 || AVIA2014 || AVIA1901)"
			elif (codeInUrl[0] == "AVIA3851"):
				normalised_pre_req = "(AVIA1850 || AVIA2701)"
			#AVIG5911. pass flight screening
			elif (codeInUrl[0] == "BABS2011"):
				#!!!
				normalised_pre_req = "(BABS1201 || BABS1202 && SCHOOL_SCIENCE)"
			elif (codeInUrl[0] == "BABS2202"):
				normalised_pre_req = "(BABS1201 && (CHEM1011 || CHEM1031))"
			elif (codeInUrl[0] == "BABS3021"):
				normalised_pre_req = "((MICR2011 && BIOS2021) || (MICR2011 && BABS2204) || (MICR2011 && BIOS2621) || (MICR2011 && BABS2264) || (MICR2011 && BIOC2201) || (BIOS2021 && BABS2204) || (BIOS2021 && BIOS2621) || (BIOS2021 && BABS2264) || (BIOS2021 && BIOC2201) || (BABS2204 && BIOS2621) || (BABS2204 && BABS2264) || (BABS2204 && BIOC2201) || (BIOS2621 && BABS2264) || (BIOS2621 && BIOC2201) || (BABS2264 && BIOC2201)"

			elif (codeInUrl[0] == "BABS3631" or codeInUrl[0] == "BABS3031"):
				normalised_pre_req = "(48_UOC)"
			elif (codeInUrl[0] == "BABS3041"):
				normalised_pre_req = "(BIOC2101 || (BIOC2181 && MICR2011) || (BIOC2181 && BABS2202))"
			elif (codeInUrl[0] == "BABS3061" or codeInUrl[0] == "BABS3121" or codeInUrl[0] == "BIOC3111"):
				normalised_pre_req = "((BIOC2101 || LIFE2101) && BIOC2201)"
			elif (codeInUrl[0] == "BABS3621"):
				normalised_pre_req = "(BIOC2101 && BIOC2201 && (3985 || 3990 || 3972 || 3973 || 3986 || 3931 || 3936))"
			elif (codeInUrl[0] == "BABS4053"):
				normalised_pre_req = "(144_UOC && 3052)"
			elif (codeInUrl[0] == "BABS6741" and career == "UG"):
				normalised_pre_req = "(30_UOC_SCIENCE)"
			elif (codeInUrl[0] == "BEES6741" and career == "UG"):
				normalised_pre_req = "(30_UOC_SCIENCE)"
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0]) or re.match('BLDG', codeInUrl[0]) or re.match('BENV', codeInUrl[0])) and re.match('\(96_UOC completed in Built Environment', normalised_pre_req) and career == "UG"):
				normalised_pre_req = "(96_UOC_BUILT_ENVIORNMENT)"
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0]) or re.match('BENV', codeInUrl[0])) and re.match('\(96_UOC completed', normalised_pre_req) and career == "UG"):
				normalised_pre_req = "(96_UOC)"

			elif (codeInUrl[0] == "BINF4910"):
				normalised_pre_req = "(126_UOC && (3647 || 3755 || 3756 || 3757 || 3715))"
			elif (codeInUrl[0] == "BINF4920"):
				normalised_pre_req = "(3647 || 3755 || 3756 || 3757 || 3715)"
			elif (codeInUrl[0] == "BIOC2101" or codeInUrl[0] == "BIOC2201"):
				normalised_pre_req = "(BABS1201 && (CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061))"
			elif (codeInUrl[0] == "BIOC2291"):
				normalised_pre_req = "(BABS1201 && (CHEM1011 || CHEM1031))"
			elif (codeInUrl[0] == "BIOC3671"):
				normalised_pre_req = "(BIOC2101 && BIOC2201 && (3985 || 3990 || 3972 || 3973 || 3986 || 3931 || 3936))"
			elif (codeInUrl[0] == "BIOM5950"):
				normalised_pre_req = "(126_UOC_3728 || 126_UOC_3757)"
			elif (codeInUrl[0] == "BIOM5960"):
				normalised_pre_req = "(STAGE_4_3749)"
			elif (codeInUrl[0] == "BIOM9510"):
				normalised_pre_req = "!(3710 || 3711 || 3683 || 3688)"
			elif (codeInUrl[0] == "BIOM9670"):
				normalised_pre_req = "(BIOM9660{DN})"
			elif (codeInUrl[0] == "BIOS3711"):
				normalised_pre_req = "((ANAT2111 || ANAT1521 || ANAT2511{CR} || ANAT1551) && PROGRAM_WAM_50)"
			elif (codeInUrl[0] == "CEIC2004"):
				normalised_pre_req = "(CHEM1021 || CHEM1041 || CEIC1001)"
			elif ((re.match('CEIC', codeInUrl[0]) or re.match('CHEM', codeInUrl[0]) or re.match('CHEN', codeInUrl[0])) and re.match('\(at least 144 Units', normalised_pre_req) and career == "UG"):
				normalised_pre_req = "(144_UOC_CHEMICAL_ENGINEERING || 144_UOC_INDUSTRIAL_CHEMICAL)"
			elif (codeInUrl[0] == "CEIC6005"):
				normalised_pre_req = "((MATS1101 || CHEM1011 || CHEM1021) && CEIC2000 && CEIC2002)"
			elif (codeInUrl[0] == "CHEM1041"):
				normalised_pre_req = "(CHEM1031 || CHEM1011{CR})"
			elif (codeInUrl[0] == "CHEM1051"):
				normalised_pre_req = "(3999 || 3992)"
			elif (codeInUrl[0] == "CHEM1061"):
				normalised_pre_req = "((3992 || 3999) && (CHEM1051 || CHEM1031 || CHEM1011{CR}))"
			elif (codeInUrl[0] == "CHEM1829"):
				#!!!
				normalised_pre_req = "(CHEM1031 && (3952 || MAJOR_VISION_SCIENCE))"
			elif (codeInUrl[0] == "CHEM2011"):
				normalised_pre_req = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061) && (MATH1011 || MATH1031 || MATH1131 || MATH1141 || MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "CHEM2021" or codeInUrl[0] == "CHEM2031" or codeInUrl[0] == "CHEM2921"):
				normalised_pre_req = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061))"
			elif (codeInUrl[0] == "CHEM2041"):
				normalised_pre_req = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061) && (MATH1031 || MATH1041 || MATH1131 || MATH1141 || MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "CHEM2828" or codeInUrl[0] == "CHEM2839"):
				normalised_pre_req = "((CHEM1011 || CHEM1031) && (CHEM1021 || CHEM1041))"
			elif (codeInUrl[0] == "CODE3100"):
				#!!!
				normalised_pre_req = "(year 1 && 2 core courses)"
			#COMD5004
			elif (codeInUrl[0] == "COMM5002"):
				#can be a normalised_co_req
				normalised_pre_req = "(COMM5001)"
			elif (codeInUrl[0] == "COMM5004"):
				normalised_pre_req = "(COMM5001 && COMM5002 && COMM5003 && (8404 || 8417) && 48_UOC)"
			elif (codeInUrl[0] == "COMM5008"):
				normalised_pre_req = "(8417 || 8404 || 8415)"
			elif (codeInUrl[0] == "COMP1400" or codeInUrl[0] == "COMP1911"):
				normalised_pre_req = "(!(SCHOOL_COMPUTER))"
			elif (codeInUrl[0] == "COMP2111"):
				normalised_pre_req = "((COMP1911 || COMP1917) && MATH1081)"
			elif (codeInUrl[0] == "COMP2121"):
				normalised_pre_req = "(COMP1917 || COMP1921 || (COMP1911 && MTRN2500))"
			elif (codeInUrl[0] == "COMP3231"):
				#!!!
				normalised_pre_req = "(((COMP1921 || COMP1927) && (COMP2121 || ELEC2142)) || (COMP1921 || COMP1927))"
			elif (codeInUrl[0] == "COMP3431"):
				normalised_pre_req = "(COMP2911 && 70_WAM)"
			elif (codeInUrl[0] == "COMP3441" or codeInUrl[0] == "COMP3511" or (codeInUrl[0] == "COMP4161" and career == "UG")):
				normalised_pre_req = "(48_UOC)"
			elif (codeInUrl[0] == "COMP3821"):
				normalised_pre_req = "(COMP1927 || COMP1921{70})"
			elif (codeInUrl[0] == "COMP3891"):
				normalised_pre_req = "((COMP1921{70} || COMP1927) && (COMP2121{70} || ELEC2142))"
			elif (codeInUrl[0] == "COMP3901" or codeInUrl[0] == "COMP3902"):
				normalised_pre_req = "(PROGRAM_WAM_80)"
			elif (codeInUrl[0] == "COMP4411"):
				normalised_pre_req = "(75_WAM && COMP1927)"
			elif (codeInUrl[0] == "COMP4431" and career == "UG"):
				#!!!
				normalised_pre_req = "(COMP1927 || (Stage_2{3267 || 3994 || 3402 || 3428 || 4810 || 4802}))"

			elif (codeInUrl[0] == "COMP4904"):
				normalised_pre_req = "(72_UOC_COMPUTER_SCIENCE_CO_OP)"
			elif (codeInUrl[0] == "COMP4910"):
				normalised_pre_req = "(HONOURS_MAJOR_COMPUTER_SCIENCE)"
			elif (codeInUrl[0] == "COMP4920"):
				#!!!
				normalised_pre_req = "(CSE_STAGE_2)"
			elif (codeInUrl[0] == "COMP4930"):
				normalised_pre_req = "(4515 || 126_UOC_COMPUTER)"
			elif (codeInUrl[0] == "COMP4941"):
				normalised_pre_req = "(COMP4930 && (75_WAM || HONOURS_MAJOR_COMPUTER_SCIENCE))"
			elif (codeInUrl[0] == "COMP6721" and career == "UG"):
				normalised_pre_req = "((MATH1081 || 6_UOC_MATH2###) && 12_UOC_COMP3###)"
			elif (codeInUrl[0] == "COMP6733"):
				normalised_pre_req = "(65_WAM && COMP3331)"
			elif (codeInUrl[0] == "COMP9018" and career == "UG"):
				normalised_pre_req = "(COMP3421{65})"
			elif (codeInUrl[0] == "COMP9018" and career == "PG"):
				normalised_pre_req = "(COMP9415{65})"
			elif (codeInUrl[0] == "COMP9242" and career == "UG"):
				normalised_pre_req = "(COMP3231{75} || COMP3891)"
			elif (codeInUrl[0] == "COMP9242" and career == "PG"):
				normalised_pre_req = "(COMP9201{75} || COMP9283)"
			elif (codeInUrl[0] == "COMP9242" and career == "PG"):
				normalised_pre_req = "((COMP9201 || COMP9283) && COMP9331)"
			elif (codeInUrl[0] == "COMP9283"):
				normalised_pre_req = "(COMP9032{70} && COMP9024)"
			#COMP9321 has normalised_co_req for postgrad
			elif (codeInUrl[0] == "COMP9321" and career == "PG"):
				normalised_pre_req = "(COMP9024)"
			elif (codeInUrl[0] == "COMP9431"):
				normalised_pre_req = "(70_WAM && COMP9024)"
			elif (codeInUrl[0] == "COMP9801"):
				normalised_pre_req = "(COMP9024{70})"

			elif (codeInUrl[0] == "COMP9844" and career == "UG"):
				normalised_pre_req = "(70_WAM && (COMP1927 || MTRN3500))"
			elif (codeInUrl[0] == "COMP9844" and career == "PG"):
				normalised_pre_req = "(70_WAM && COMP9024)"
			elif (codeInUrl[0] == "CRIM2014" or codeInUrl[0] == "CRIM2031" or codeInUrl[0] == "CRIM2032" or codeInUrl[0] == "CRIM2034" or codeInUrl[0] == "CRIM2036" or codeInUrl[0] == "CRIM2037" or codeInUrl[0] == "CRIM2038"):
				normalised_pre_req = "(30_UOC_LEVEL_1_CRIM && (CRIM1010 || CRIM1011))"
			elif (codeInUrl[0] == "CRIM2020"):
				normalised_pre_req = "(24_UOC_LEVEL_1_CRIM && CRIM1010 && CRIM1011)"
			elif (codeInUrl[0] == "CRIM2021"):
				normalised_pre_req = "(CRIM2020 && !(FACULTY_LAW))"
			#elif (codeInUrl[0] == "CRIM2021"):
		#		normalised_pre_req = "(30_UOC_LEVEL_1_CRIMINOLOGY)"
				#CRIM3### skipped
			elif (re.match('CRIM4', codeInUrl[0])):
				normalised_pre_req = "(HONOURS_MAJOR_CRIMINOLOGY)"
				#CRIM5### skipped
			elif (codeInUrl[0] == "CVEN4002" or codeInUrl[0] == "CVEN4003" or codeInUrl[0] == "CVEN4050"):
				normalised_pre_req = "(132_UOC)"
			elif (codeInUrl[0] == "CVEN4030"):
				normalised_pre_req = "(132_UOC && 63_WAM)"
			elif (codeInUrl[0] == "CVEN4308"):
				normalised_pre_req = "(CVEN3301 && CVEN2002)"
			elif (codeInUrl[0] == "ECON2107"):
				normalised_pre_req = "(ECON1101 && (ECON1203 || ECON2292 || MATH1041 || MATH2801 || MATH2841 || MATH2901))"
			elif (codeInUrl[0] == "ECON3109" or codeInUrl[0] == "ECON3119"):
			#!!!
				normalised_pre_req = "(ECON2101 || ECON2103 || 48_UOC_ARTS_SOCIAL_SCIENCES)"
			elif (codeInUrl[0] == "ECON3114" or codeInUrl[0] == "ECON3117"):
				normalised_pre_req = "(ECON2101 || ACTL2131 || ACTL2111 || (84_UOC && (3155 || 3502 || 3554 || 4501 || 3558 || 3593 || 3835 || 3967 || 3568 || 3567 || 3584 || 4733 || 3522 || 3521 || 3462 || 3559 || 3529 || 3764 || 3136)"
			elif (codeInUrl[0] == "ECON5257"):
				normalised_pre_req = "(8409 || 8415)"
			elif (re.match('ECON5', codeInUrl[0]) and re.match('\(7412 || approval', normalised_pre_req)):
				normalised_pre_req = "(7412 || SCHOOL_APPROVAL)"
			elif (re.match('ECON6', codeInUrl[0]) and re.match('\(8412 || approval', normalised_pre_req)):
				normalised_pre_req = "(8412 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ECON6205"):
				normalised_pre_req = "(ECON6003 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ECON6301" or codeInUrl[0] == "ECON6302" or codeInUrl[0] == "ECON6303" or codeInUrl[0] == "ECON6307"):
				#it's a normalised_co_req as well
				normalised_pre_req = "(ECON6001)"
			elif (codeInUrl[0] == "ECON6350"):
				#it's a normalised_co_req as well
				normalised_pre_req = "(ECON6001 && ECON6002 && ECON6003 && ECON6004)"
			elif (codeInUrl[0] == "EDST2002"):
				normalised_pre_req = "(48_UOC_LEVEL_1 && EDST1101 && EDST1104 && EDST2003 && (3446 || 3462 || 4054 || 4076 || 4058 || 4059 || 4061 || 4062))"
			elif (codeInUrl[0] == "EDST2003"):
				normalised_pre_req = "(24_UOC_LEVEL_1 && (EDST1101 || EDST1104) && (3446 || 3462 || 4054 || 4058 || 4059 || 4061 || 4062 || 4076))"
			#elif (re.match('EDST20', codeInUrl[0]) and re.match('24', normalised_pre_req)):
				#normalised_pre_req = "24_UOC_LEVEL_1"
				#EDST skipped
			elif (re.match('ELEC', codeInUrl[0])):
				normalised_pre_req = normalised_pre_req.upper()
				if (codeInUrl[0] == "ELEC2142"):
					normalised_pre_req = "(ELEC2141 && COMP1921)"

			elif (codeInUrl[0] == "FINS2622"):
				#also a normalised_co_req
				normalised_pre_req = "(FINS1612 && FINS2624)"
			elif (codeInUrl[0] == "FINS3202"):
				normalised_pre_req = "(FINS2101 && (FINSD13554 || FINSBH3565))"
			elif (codeInUrl[0] == "FINS3303"):
				normalised_pre_req = "(FINS3202 && (FINSD13554 || FINSBH3565))"
			elif (codeInUrl[0] == "FINS3775" or codeInUrl[0] == "FINS4775"):
				normalised_pre_req = "(FINS2624{70} && ECON1203)"
			elif (codeInUrl[0] == "FINS4774" or codeInUrl[0] == "FINS4776" or codeInUrl[0] == "FINS4777" or codeInUrl[0] == "FINS4779"):
				#also a normalised_co_req
				normalised_pre_req = "(FINS3775 || FINS4775)"
			elif (codeInUrl[0] == "FINS4781"):
				normalised_pre_req = "(HONOURS_MAJOR_FINANCE)"
			elif (codeInUrl[0] == "FINS5511"):
				#also a normalised_co_req
				normalised_pre_req = "(ACCT5930 && ECON5103)"
			elif (codeInUrl[0] == "FINS5512"):
				#also a normalised_co_req
				normalised_pre_req = "(ACCT5906 || ECON5103 || 9273 || 5273 || 7273 || 8007)"
			elif (codeInUrl[0] == "FINS5513"):
				normalised_pre_req = "(COMM5005 || ECON5248 || COMM5011 || 5273 || 7273 || 8413 || 9273 || (REST0001 && 8127))"
			elif (codeInUrl[0] == "FINS5514" or codeInUrl[0] == "FINS5517"):
				#also a normalised_co_req
				normalised_pre_req = "(FINS5513)"
			elif (codeInUrl[0] == "FINS5516" or codeInUrl[0] == "FINS5530" or codeInUrl[0] == "FINS5531" or codeInUrl[0] == "FINS5542"):
				#also a normalised_co_req
				normalised_pre_req = "(FINS5513 || 8406)"
			elif (codeInUrl[0] == "FINS5522"):
				#also a normalised_co_req
				normalised_pre_req = "((FINS5512 and FINS5513) || 8406)"
			elif (codeInUrl[0] == "FINS5533"):
				#also a normalised_co_req
				normalised_pre_req = "(FINS5513 || FINS5561 || 8406)"
			elif (codeInUrl[0] == "FINS5543"):
				normalised_pre_req = "(7273 || 5273 || 9273)"
			elif (codeInUrl[0] == "FINS5566"):
				#also a normalised_co_req
				normalised_pre_req = "(FINS5512 || 8406 || 8413)"
			elif (codeInUrl[0] == "FINS5568"):
				normalised_pre_req = "(FINS5513 && (8404 || 8417 || 8428) && 48_UOC)"
			elif (codeInUrl[0] == "FINS5579"):
				#also a normalised_co_req
				normalised_pre_req = "(FINS3775 || FINS4775 || FINS5575)"

			elif (codeInUrl[0] == "FOOD6804"):
				normalised_pre_req = "(CHEM3811 || INDC2003)"

			elif (codeInUrl[0] == "GBAT9104" or codeInUrl[0] == "GBAT9113"):
				normalised_pre_req = "((8616 && 48_UOC) || (5457 && 36_UOC))"
			elif (re.match('GBAT', codeInUrl[0]) and re.match('\(', normalised_pre_req)):
				normalised_pre_req = "(8616 || 7333 || 5457)"
			elif (codeInUrl[0] == "GEOL4141"):
				normalised_pre_req = "(24_UOC_LEVEL_3_GEOL || 24_UOC_LEVEL_3_GEOS)"
			elif (codeInUrl[0] == "GEOS2101"):
				#!!!
				normalised_pre_req = "(GEOL1111 || GEOS1111 || GEOL1211 || GEOS1211 || GEOS1701 || BIOS1101)"
			elif (codeInUrl[0] == "GEOS3371"):
				normalised_pre_req = "(GEOS3331 && LEVEL_3_GEOS)"
			elif (codeInUrl[0] == "GEOS3621"):
				normalised_pre_req = "((GEOS2641 || ENVS2030) && GEOS2721)"
			elif (codeInUrl[0] == "GEOS6734"):
				normalised_pre_req = "(BEES2041 || MATH2089 || MATH2099 || MATH2801 || MATH2841 || MATH2859 || MATH2901)"
			elif (codeInUrl[0] == "HESC2452"):
				normalised_pre_req = "((ANAT2451 || (ANAT3131 && ANAT3141)) && (BIOM2451 || SESC2451))"
			elif (codeInUrl[0] == "HESC2501"):
				normalised_pre_req = "(BIOC2181 && PHSL2501)"
			elif (codeInUrl[0] == "HESC3504"):
				normalised_pre_req = "(HESC2501 && HESC1511 && (PSYC1011 || HESC1531))"
			elif (codeInUrl[0] == "HESC3541"):
				normalised_pre_req = "(HESC2501 && PHSL2502 && (PATH2202 || PATH2201) && PHSL2501)"
			elif (codeInUrl[0] == "IDES4321" or codeInUrl[0] == "IDES4372"):
				normalised_pre_req = "(96_UOC_BUILT)"
			elif (codeInUrl[0] == "INDC2003"):
				normalised_pre_req = "((CHEM1021 || CHEM1041) || CEIC1001)"
			elif (codeInUrl[0] == "INFS2101"):
				normalised_pre_req = "(INFS1609 && INFS2603 && (INFSB13554 || INFSCH3964 || INFSCH3971))"
			elif (codeInUrl[0] == "INFS3202"):
				normalised_pre_req = "(INFS2101 && (INFSB13554 || INFSCH3971 || INFSCH3964))"
			elif (codeInUrl[0] == "INFS3303"):
				normalised_pre_req = "(INFS3202 && (INFSB13554 || INFSCH3971 || INFSCH3964))"
			elif (codeInUrl[0] == "INFS3603" or codeInUrl[0] == "INFS3604" or codeInUrl[0] == "INFS3631" or codeInUrl[0] == "INFS3632" or codeInUrl[0] == "INFS3633"):
				normalised_pre_req = "(INFS1602 && 72_UOC)"
			elif (codeInUrl[0] == "INFS3605"):
				normalised_pre_req = "(INFS2603 && INFS2605 && 72_UOC)"
			elif (codeInUrl[0] == "INFS3608"):
				normalised_pre_req = "((INFS1602 && INFS1603) || MAJOR_SOFTWARE_ENGINEERING)"
			elif (codeInUrl[0] == "INFS3611"):
				normalised_pre_req = "(INFS2603 && (INFS1609 || INFS2609) && 72_UOC)"
			elif (codeInUrl[0] == "INFS3634"):
				normalised_pre_req = "(INFS2605 && INFS2608 && 72_UOC)"
			elif (codeInUrl[0] == "INFS3774"):
				normalised_pre_req = "(INFS2607 && 72_UOC)"
			elif (codeInUrl[0] == "INFS4796"):
				normalised_pre_req = "(INFS4795 && (INFSCH3971 || INFSBH3554 || INFSAH3502 || INFSAH3979 || INFSAH3584))"
			elif (codeInUrl[0] == "INFS4887"):
				normalised_pre_req = "(HONOURS_MAJOR_INFORMATION_SYSTEMS && INFS4886)"
			elif (codeInUrl[0] == "INFS4795" or re.match('INFS48', codeInUrl[0])):
				normalised_pre_req = "(HONOURS_MAJOR_INFORMATION_SYSTEMS)"
			elif (codeInUrl[0] == "INFS5731" or codeInUrl[0] == "INFS5732"):
				normalised_pre_req = "(8407 || 8435 || 8425 || 8426)"
			elif (codeInUrl[0] == "INFS5740"):
				normalised_pre_req = "(8407 || 8425 || 8435 || 8426 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "INFS5935"):
				normalised_pre_req = "(8425 || 8435)"
			elif (codeInUrl[0] == "INFS5936"):
				normalised_pre_req = "(8435)"
			elif (codeInUrl[0] == "INFS5978"):
				#also a normalised_co_req
				normalised_pre_req = "(ACCT5930)"
			elif (codeInUrl[0] == "INFS5997"):
				normalised_pre_req = "((8404 || 8417) && 75_WAM)"
			elif (codeInUrl[0] == "INST1005"):
				#!!!
				normalised_pre_req = "(MAJOR_INTERNATIONAL_STUDIES)"
			elif (codeInUrl[0] == "INST3900"):
				#!!!
				normalised_pre_req = "(96_UOC && MAJOR_INTERNATIONAL_STUDIES)"
			#JAPN5011 skipped
			#JURD skipped
			#KORE skipped
			#LAWS skipped
			#LING skipped

			elif (codeInUrl[0] == "MANF3610"):
				normalised_pre_req = "((ENGG1811 || COMP1911) && MATH2089)"

			elif (codeInUrl[0] == "MANF4100"):
				normalised_pre_req = "(MANF3100 && MANF3510)"
			elif (codeInUrl[0] == "MANF4430"):
				normalised_pre_req = "(MATH2089)"
			
			elif (codeInUrl[0] == "MARK3202" or codeInUrl[0] == "MARK3303"):
				normalised_pre_req = "(MARK2101 && MARKB13554)"
			elif (re.match('MARK42', codeInUrl[0])):
				#!!!
				normalised_pre_req = "(HONOURS_MAJOR_MARKETING)"
			elif (codeInUrl[0] == "MARK5810" or codeInUrl[0] == "MARK5812" or codeInUrl[0] == "MARK5815" or codeInUrl[0] == "MARK5817"):
				#also a normalised_co_req
				normalised_pre_req = "(MARK5800 || MARK5801 || MARK5813)"
			elif (codeInUrl[0] == "MARK5811" or codeInUrl[0] == "MARK5813" or codeInUrl[0] == "MARK5822"):
				#also a normalised_co_req
				normalised_pre_req = "(MARK5700 || MARK5800 || MARK5801)"
			elif (codeInUrl[0] == "MARK5814"):
				#also a normalised_co_req
				normalised_pre_req = "(MARK5700 || MARK5800 || MARK5801 || MARK5813)"
			elif (codeInUrl[0] == "MARK5816"):
				#also a normalised_co_req
				normalised_pre_req = "(MARK5800 || MARK5801 || MARK5813 || 8406)"
			elif (codeInUrl[0] == "MARK5819"):
				normalised_pre_req = "(MARK5800 || MARK5801 || (7291 || 5291 || 8291 || 8281))"
			elif (codeInUrl[0] == "MARK5820"):
				#also a normalised_co_req
				normalised_pre_req = "(MARK5800 || MARK5801 || (7291 || 5291 || 8291 || 8281))"
			elif (re.match('MARK60', codeInUrl[0]) and re.match('\(', normalised_pre_req)):
				normalised_pre_req = "(MAJOR_MARKETING && CAREER_POSTGRADUATE)"
			elif (re.match('MARK61', codeInUrl[0]) and re.match('\(', normalised_pre_req)):
				normalised_pre_req = "(7414 || 8423)"
			elif (codeInUrl[0] == "MATH1241"):
				normalised_pre_req = "(MATH1131{CR} || MATH1141{CR})"
			elif (codeInUrl[0] == "MATH2111" or codeInUrl[0] == "MATH2130" or codeInUrl[0] == "MATH2221" or codeInUrl[0] == "MATH2601" or codeInUrl[0] == "MATH2620" or codeInUrl[0] == "MATH2621"):
				normalised_pre_req = "(MATH1231{70} || MATH1241{70} || MATH1251{70})"
			elif (codeInUrl[0] == "MATH2301"):
				normalised_pre_req = "(MATH1031{CR} || MATH1231 || MATH1241 || MATH1251)"
			elif (codeInUrl[0] == "MATH2701"):
				normalised_pre_req = "(MATH1231{CR} || MATH1241{CR} || MATH1251{CR} && (ADVANCED_MAJOR_MATH || ADVANCED_MAJOR_SCIENCE))"
			elif (codeInUrl[0] == "MATH2801" or codeInUrl[0] == "MATH2901"):
				normalised_pre_req = "(MATH1231 || MATH1241 || MATH1251 || (3653 && (MATH1131 || MATH1141)))"
			elif (codeInUrl[0] == "MATH2859"):
				normalised_pre_req = "(MATH1231 || MATH1241 || ((3648 || 3651 || 3652 || 3653 || 3749 || 3982) && (MATH1131 || MATH1141)))"
			elif (codeInUrl[0] == "MATH2871"):
				normalised_pre_req = "(MATH1041 || ECON1203 || ECON2292 || PSYC2001 || MATH1231 || MATH1241 || MATH1251)"
			elif (codeInUrl[0] == "MATH2881"):
				normalised_pre_req = "(MATH1231 || MATH1241 || MATH1251 || ECON1203{CR})"
			elif (codeInUrl[0] == "MATH2931"):
				normalised_pre_req = "(MATH2901 || MATH2801{DN})"
			elif (re.match('MATH30', codeInUrl[0]) or codeInUrl[0] == "MATH3511" or codeInUrl[0] == "MATH3521" or codeInUrl[0] == "MATH3570"):
				normalised_pre_req = "(12_UOC_LEVEL_2_MATH)"
			elif (codeInUrl[0] == "MATH3101"):
				normalised_pre_req = "(12_UOC_LEVEL_2_MATH && (((MATH2011 || MATH2111) && (MATH2120 || MATH2130 || MATH2121 || MATH2221)) || (MATH2019{DN} && MATH2089) || (MATH2069{CR} && MATH2099)))"
			elif (codeInUrl[0] == "MATH3121" or codeInUrl[0] == "MATH3261"):
				normalised_pre_req = "(12_UOC_LEVEL_2_MATH && (((MATH2011 || MATH2111) && (MATH2120 || MATH2130 || MATH2121 || MATH2221)) || (MATH2019{DN} && MATH2089) || (MATH2069{DN} && MATH2099)))"	
			elif (codeInUrl[0] == "MATH3161"):
				normalised_pre_req = "(12_UOC_LEVEL_2_MATH && (((MATH2011 || MATH2111 || MATH2510) && (MATH2501 || MATH2601)) || (MATH2019{DN} && MATH2089) || (MATH2069{CR} && MATH2099)))"	
			elif (codeInUrl[0] == "MATH3411"):
				normalised_pre_req = "(MATH1081 || MATH1231{CR} || MATH1241{CR} || MATH1251{CR} || MATH2099)"
			elif (codeInUrl[0] == "MATH3531"):
				normalised_pre_req = "(12_UOC_LEVEL_2_MATH && (MATH2011 || MATH2111 || MATH2069))"
			elif (codeInUrl[0] == "MATH3560"):
				normalised_pre_req = "(6_UOC_LEVEL_2_MATH)"
			elif (codeInUrl[0] == "MATH3611"):
				normalised_pre_req = "((12_UOC_LEVEL_2_MATH && PROGRAM_WAM_70 && (MATH2111 || MATH2011{CR} || MATH2510{CR}) || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "MATH3701"):
				normalised_pre_req = "((12_UOC_LEVEL_2_MATH && PROGRAM_WAM_70 && (((MATH2111 || MATH2011{CR} || MATH2510{CR}) && (MATH2601 || MATH2501{CR}))) || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "MATH3711"):
				normalised_pre_req = "((12_UOC_LEVEL_2_MATH && PROGRAM_WAM_70 && (MATH2601 || MATH2501{CR})) || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "MATH3801"):
				normalised_pre_req = "((MATH2501 || MATH2601) && (MATH2011 || MATH2111 || MATH2510 || MATH2610) && (MATH2801 || MATH2901))"
			elif (codeInUrl[0] == "MATH3851"):
				normalised_pre_req = "((MATH2801 || MATH2901) && (MATH2831 || MATH2931))"
			elif (codeInUrl[0] == "MATH3901"):
				normalised_pre_req = "((MATH2901 || MATH2801{DN}) && (MATH2501 || MATH2601) && (MATH2011 || MATH2111 || MATH2510 || MATH2610))"
			elif (codeInUrl[0] == "MATH3911"):
				normalised_pre_req = "(MATH2931 || MATH2831{DN})"
			elif (codeInUrl[0] == "MBAX6271"):
				normalised_pre_req = "(5950 || 7315 || 8616)"
			elif (codeInUrl[0] == "MBAX6272" or codeInUrl[0] == "MBAX6273"):
				normalised_pre_req = "(7315 || 8616 || 8625)"
			elif (codeInUrl[0] == "MBAX6274"):
				normalised_pre_req = "((MNGT6271 || MBAX6271) && (5950 || 7315 || 8355 || 8616 || 8625))"
			elif (codeInUrl[0] == "MBAX9104"):
				normalised_pre_req = "(8625 && 48_UOC)"
			#MDIA skipped
			elif (codeInUrl[0] == "MFIN6213"):
				normalised_pre_req = "(MFIN6201 && MFIN6210 && 8406 && 85_WAM)"
			elif (codeInUrl[0] == "MGMT2101"):
				normalised_pre_req = "(MGMT1101)"
			elif (codeInUrl[0] == "MGMT2105"):
				normalised_pre_req = "(MGMT1101 && 48_UOC)"
			elif (codeInUrl[0] == "MGMT2718" or codeInUrl[0] == "MGMT3728"):
				#changed
				normalised_pre_req = "(3_UOC_LEVEL_1_MGMT)"
			elif (codeInUrl[0] == "MGMT3001"):
				normalised_pre_req = "(MGMT1001 || 12_UOC_BUSINESS)"
			elif (codeInUrl[0] == "MGMT3003"):
				normalised_pre_req = "(48_UOC)"
			elif (codeInUrl[0] == "MGMT4101" or codeInUrl[0] == "MGMT4500" or codeInUrl[0] == "MGMT4501"):
				normalised_pre_req = "(4501 && HONOURS_MAJOR_INTERNATIONAL_BUSINESS)"
			elif (codeInUrl[0] == "MGMT4103" or codeInUrl[0] == "MGMT4750" or codeInUrl[0] == "MGMT4751"):
				normalised_pre_req = "(4501 && HONOURS_MAJOR_MANAGEMENT)"
			elif (codeInUrl[0] == "MGMT4104" or codeInUrl[0] == "MGMT4738" or codeInUrl[0] == "MGMT4739"):
				normalised_pre_req = "(4501 && HONOURS_MAJOR_HUMAN_RESOURCE_MANAGEMENT)"
			elif (codeInUrl[0] == "MGMT5980" or codeInUrl[0] == "MGMT5981"):
				normalised_pre_req = "(8407)"
			elif (codeInUrl[0] == "MICR3621"):
				normalised_pre_req = "((MICR2011 && BIOS2021) || (MICR2011 && BABS2204) || (MICR2011 && BIOS2621) || (MICR2011 && BABS2264) || (MICR2011 && BIOC2201) || (BIOS2021 && BABS2204) || (BIOS2021 && BIOS2621) || (BIOS2021 && BABS2264) || (BIOS2021 && BIOC2201) || (BABS2204 && BIOS2621) || (BABS2204 && BABS2264) || (BABS2204 && BIOC2201) || (BIOS2621 && BABS2264) || (BIOS2621 && BIOC2201) || (BABS2264 && BIOC2201)"
			elif ((re.match('MINE5', codeInUrl[0]) or re.match('MNNG5', codeInUrl[0])) and re.match('\(', normalised_pre_req)):
				normalised_pre_req = "(MINENS5059 || MINEUS8059 || MINESS5040)"
			elif (codeInUrl[0] == "MINE8640"):
				normalised_pre_req = "(MINEMS5059 || MINETS8059 || MINEIS8058 || MINEJS8335 || MINERS5335)"
			elif (codeInUrl[0] == "MINE8660"):
				normalised_pre_req = "(MINEMS5059)"
			elif (codeInUrl[0] == "MINE8680" or codeInUrl[0] == "MINE8690"):
				normalised_pre_req = "(MINENS5059 || (MINE8140 && MINEMS5059))"
			elif (codeInUrl[0] == "MINE8720"):
				normalised_pre_req = "(MINEUS8059 || (MINE8140 && (MINEIS8058 || MINEJS8335 || MINERS5335 || MINETS8059)"
			elif (codeInUrl[0] == "MMAN4010"):
				normalised_pre_req = "(138_UOC && MMAN3000)"
			elif (codeInUrl[0] == "MMAN4410"):
				normalised_pre_req = "(MMAN2400)"
			elif (codeInUrl[0] == "MMAN9001"):
				normalised_pre_req = "(18_UOC && (MECHIS8338 || MANFCS8338 || MECHAS8539 || MANFAS8539))"
			elif (codeInUrl[0] == "MMAN9002"):
				normalised_pre_req = "((42_UOC && MMAN9001 && (MECHIS8338 || MANFCS8338 || MECHAS8539 || MANFAS8539))"
			elif (codeInUrl[0] == "MMAN9012" or codeInUrl[0] == "MMAN9024"):
				normalised_pre_req = "(65_WAM)"

			#MNGT skipped
			#MODL skipped	
			elif (codeInUrl[0] == "MTRN3500"):
				normalised_pre_req = "(MTRN2500)"
			elif (codeInUrl[0] == "MTRN4110"):
				normalised_pre_req = "((ELEC1111 || ELEC1112) && MTRN2500 && MMAN3200)"
			elif (codeInUrl[0] == "MTRN4230"):
				normalised_pre_req = "((MMAN2300 || MMAN3300) && (MTRN2500 && MTRN3020))"
			#MUSC skipped
			elif (codeInUrl[0] == "NANO3002"):
				normalised_pre_req = "(NANO2002 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "NEUR2201"):
				normalised_pre_req = "(12_UOC_BABS_BIOS || 12_UOC_PSYC)"
			elif (codeInUrl[0] == "OPTM4211" or codeInUrl[0] == "OPTM4271"):
				normalised_pre_req = "(OPTM4110 && OPTM4131 && OPTM4151)"
			elif (codeInUrl[0] == "OPTM7203"):
				normalised_pre_req = "(OPTM7103 && (8760 || 5665 || 7435))"
			elif (codeInUrl[0] == "OPTM7205"):
				normalised_pre_req = "(OPTM7104 && (8760 || 5665 || 7435 || 5523))"
			elif (codeInUrl[0] == "OPTM7213"):
				normalised_pre_req = "(7436)"
			elif (codeInUrl[0] == "OPTM7301"):
				normalised_pre_req = "(OPTM7309 && (8760 || 5665 || 7435))"
			elif (codeInUrl[0] == "PATH2201"):
				normalised_pre_req = "(ANAT2241 && (ANAT2111 || ANAT1521 || PHSL2101 || BIOC2101 || BIOC2181))"
			elif (codeInUrl[0] == "PATH3207"):
				normalised_pre_req = "((PATH2201 || PATH2202) && (ANAT2111 || ANAT2511 || ANAT1521 || ANAT1551))"
			elif (codeInUrl[0] == "PHAR2011"):
				normalised_pre_req = "(6_UOC_LEVEL_1_BABS_BIOS && (3992 || PHSL2101) && 12_UOC_LEVEL_1_CHEM && 6_UOC_LEVEL_1_MATH)"
			elif (codeInUrl[0] == "PHAR3101" or codeInUrl[0] == "PHAR3102" or codeInUrl[0] == "PHAR3251"):
				normalised_pre_req = "(PHAR2011 || PHAR2211)"
			elif (re.match('PHCM9', codeInUrl[0]) and re.match('\(Student', normalised_pre_req)):
				normalised_pre_req = "(FACULTY_MEDICINE && CAREER_POSTGRADUATE)"
			elif (codeInUrl[0] == "PHSL2101"):
				normalised_pre_req = "(6_UOC_LEVEL_1_BABS_BIOS && 6_UOC_LEVEL_1_CHEM && 6_UOC_LEVEL_1_MATH)"
			elif (codeInUrl[0] == "PHSL2201"):
				normalised_pre_req = "(PHSL2101)"
			elif (codeInUrl[0] == "PHSL2501"):
				normalised_pre_req = "(BABS1201 && CHEM1831 && MATH1041)"
			elif (codeInUrl[0] == "PHSL3211" or codeInUrl[0] == "PHSL3221"):
				normalised_pre_req = "((PHSL2101 || PHSL2121 || PHSL2501) && (PHSL2201 || PHSL2221 || PHSL2502))"
			elif (codeInUrl[0] == "PHYS1231"):
				normalised_pre_req = "(PHYS1131 || PHYS1141 || PHYS1121{65})"
			elif (codeInUrl[0] == "PHYS2010" or codeInUrl[0] == "PHYS2040" or codeInUrl[0] == "PHYS2050"):
				normalised_pre_req = "((PHYS1002 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "PHYS2020"):
				normalised_pre_req = "((PHYS1002 || PHYS1022 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1021 || MATH1231 || MATH1241 || MATH1031))"
			elif (codeInUrl[0] == "PHYS2030"):
				normalised_pre_req = "((PHYS1002 || PHYS1022 || PHYS1111 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1021 || MATH1131 || MATH1141 || MATH1031))"
			elif (codeInUrl[0] == "PHYS2060"):
				normalised_pre_req = "((PHYS1002 || PHYS1022 || PHYS1111 || PHYS1221 || PHYS1231 || PHYS1241) && (MATH1021 || MATH1131 || MATH1141 || MATH1031))"
			elif (codeInUrl[0] == "PHYS2110" or codeInUrl[0] == "PHYS2120"):
				normalised_pre_req = "((PHYS1221 || PHYS1231 || PHYS1241) && (MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "PHYS2210"):
				normalised_pre_req = "((PHYS1221 || PHYS1231 || PHYS1241) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3011"):
				normalised_pre_req = "(((PHYS2040 && PHYS2050) || (PHYS2110 && PHYS2210)) && (MATH2221 || MATH2121) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3021"):
				normalised_pre_req = "(((PHYS2040 && PHYS2060) || (PHYS2110 && PHYS2210)) && (MATH2221 || MATH2121) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3050"):
				normalised_pre_req = "(PHYS3010 || PHYS3210{CR})"
			elif (codeInUrl[0] == "PHYS3230"):
				normalised_pre_req = "((PHYS2011 || PHYS2050 || PHYS2939) && (MATH2011 || MATH2111) && (MATH2120 || MATH2130))"
			elif (codeInUrl[0] == "PHYS3410"):
				normalised_pre_req = "(PHYS2210 || (PHYS2060 && PHYS2410))"
			elif (codeInUrl[0] == "PHYS3410"):
				normalised_pre_req = "((PHYS2120 || PHYS2010) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS3550"):
				normalised_pre_req = "((PHYS1002 || PHYS1231 || PHYS1241 || PHYS1221) && (MATH2011 || MATH2111))"
			elif (codeInUrl[0] == "PHYS4949"):
				normalised_pre_req = "((PHYS3010 || PHYS3080) && 3644)"

			#POLS5100 skipped	
			elif (codeInUrl[0] == "PSYC2001"):
				normalised_pre_req = "(PSYC1001 && PSYC1011 && PSYC1111)"
			elif (codeInUrl[0] == "PSYC3331"):
				normalised_pre_req = "((PSYC2001 || PSYC2061 || PSYC2101) || (HESC3504 && 3871))"
			elif (re.match('PSYC7', codeInUrl[0]) and re.match('\(Restricted', normalised_pre_req)):
				normalised_pre_req = re.sub(r'^\([A-Za-z ]+', '(', normalised_pre_req, flags=re.IGNORECASE)
				normalised_pre_req = re.sub(r'&&', '||', normalised_pre_req)
			elif (codeInUrl[0] == "PTRL7011"):
				normalised_pre_req = "(36_UOC)"
			elif (re.match('RISK', codeInUrl[0])):
				normalised_pre_req = re.sub(r' of Actuarial Studies', '', normalised_pre_req, flags=re.IGNORECASE)
				normalised_pre_req = re.sub(r'Program ', '', normalised_pre_req, flags=re.IGNORECASE)
			elif (codeInUrl[0] == "SAED4403"):
				normalised_pre_req = "(SAED3404)"
			elif (codeInUrl[0] == "SAED4491"):
				normalised_pre_req = "(SAED2401 && SAED2406 && SAED3491 && SAED3402 && SAED3404 && SAED3407)"

			elif (codeInUrl[0] == "SAHT4213"):
				normalised_pre_req = "(SAHT4211)"

			#SART4043 skipped
			elif (codeInUrl[0] == "SART9738"):
				normalised_pre_req = "(SART9732)"
			elif (codeInUrl[0] == "SCOM3021"):
				#!!!
				normalised_pre_req = "((SCOM1021 || SCOM2014) && SCOM2021)"
			elif (codeInUrl[0] == "SENG1031"):
				#???
				normalised_pre_req = "(MAJOR_SOFTWARE_ENGINEERING || MAJOR_BIOINFOMATICS)"
			elif (codeInUrl[0] == "SENG2021"):
				normalised_pre_req = "((SENG2011 || COMP2911) && MAJOR_SOFTWARE_ENGINEERING)"
			elif (codeInUrl[0] == "SENG4904"):
				normalised_pre_req = "(MAJOR_SOFTWARE_ENGINEERING_CO_OP)"
			elif (codeInUrl[0] == "SENG4910"):
				normalised_pre_req = "(126_UOC_SENGA1)"
			elif (codeInUrl[0] == "SENG4921"):
				normalised_pre_req = "(MAJOR_SOFTWARE_ENGINEERING)"
			#SOCF5101 skiiped
			#SOCW skipped
			#SOMA4045 skipped
			elif (codeInUrl[0] == "SOMA9718"):
				normalised_pre_req = "(SOMA9717)"
			elif (codeInUrl[0] == "SRAP3002"):
				normalised_pre_req = "((SRAP2002 && SRAP3000 && SRAP3001) || (SLSP2002 && SLSP3000 && SLSP3001) || (SRAP2001 && SRAP2002) || (SLSP2001 && SLSP2002))"
			elif (codeInUrl[0] == "SRAP3006"):
				normalised_pre_req = "(SRAP1000 && SRAP1001 && SRAP2001 && SRAP2002 && DIPP1112 && MAJOR_SOCIAL_RESEARCH_AND_POLICY)"
			elif (re.match('SRAP405', codeInUrl[0])):
				normalised_pre_req = "(MAJOR_SOCIAL_RESEARCH_AND_POLICY)"
			#SRAP5 skipped
			elif (codeInUrl[0] == "TABL1710"):
				normalised_pre_req = "(!(4733 || 4737 || 4744))"
			elif (codeInUrl[0] == "TABL2712" or codeInUrl[0] == "TABL2731" or codeInUrl[0] == "TABL2732" or codeInUrl[0] == "TABL3761" or codeInUrl[0] == "TABL3771" or codeInUrl[0] == "TABL3791"):
				normalised_pre_req = "(LEGT1710 || TABL1710 || 12_UOC_BUSINESS)"
			elif (codeInUrl[0] == "TABL2741"):
				normalised_pre_req = "((LEGT1710 || TABL1710) && (!(4733 || 4737 || 4744)))"
			elif (codeInUrl[0] == "TABL3003" or codeInUrl[0] == "TABL3005" or codeInUrl[0] == "TABL3006" or codeInUrl[0] == "TABL3007" or codeInUrl[0] == "TABL3015" or codeInUrl[0] == "TABL3016" or codeInUrl[0] == "TABL3020" or codeInUrl[0] == "TABL3022" or codeInUrl[0] == "TABL3025" or codeInUrl[0] == "TABL3028" or codeInUrl[0] == "TABL3031" or codeInUrl[0] == "TABL3040" or codeInUrl[0] == "TABL3044"):
				normalised_pre_req = "(48_UOC)"
			elif (codeInUrl[0] == "TABL3010" or codeInUrl[0] == "TABL3026"):
				normalised_pre_req = "(TABL2751 || LEGT2751 || 48_UOC_4620)"
			elif (codeInUrl[0] == "TABL5512"):
				normalised_pre_req = "(8409 || 8415)"
			elif (codeInUrl[0] == "TABL5517"):
				normalised_pre_req = "(TABL5511 || LEGT5511 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "TABL5541" or codeInUrl[0] == "TABL5551"):
				#also a normalised_co_req
				normalised_pre_req = "(LEGT5511 || TABL5511 || LEGT5512 || TABL5512)"
			elif (codeInUrl[0] == "ZBUS2902" or codeInUrl[0] == "ZBUS3901" or codeInUrl[0] == "ZBUS3902"):
				normalised_pre_req = "(4462 && SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ZEIT2307" or codeInUrl[0] == "ZGEN2222"):
				normalised_pre_req = "(36_UOC_LEVEL_1)"
			elif (codeInUrl[0] == "ZEIT2904"):
				normalised_pre_req = "(ZEIT2903 && 4469)"
			elif (codeInUrl[0] == "ZEIT3903"):
				normalised_pre_req = "(96_UOC && 4469)"
			elif (codeInUrl[0] == "ZEIT3904"):
				normalised_pre_req = "(ZEIT3903 && 4469)"
			elif (codeInUrl[0] == "ZEIT4003"):
				#???
				normalised_pre_req = "(ZEIT2500 && (ZEIT2503 || ZEIT2602))"
			elif (codeInUrl[0] == "ZEIT4902"):
				normalised_pre_req = "(FACULTY_CDF)"
			elif (codeInUrl[0] == "ZHSS2427"):
				normalised_pre_req = "((ZHSS1201 && ZHSS1202) || (ZHSS1401 && ZHSS1402))"
			elif (codeInUrl[0] == "ZHSS2506" or codeInUrl[0] == "ZHSS3501" or codeInUrl[0] == "ZHSS3505"):
				normalised_pre_req = "((ZHSS1102 && ZHSS1202) || (ZHSS1102 && ZHSS1302) || (ZHSS1102 && ZHSS1304) || (ZHSS1102 && ZHSS1402) || (ZHSS1102 && ZPEM1202) || (ZHSS1202 && ZHSS1302) || (ZHSS1202 && ZHSS1304) || (ZHSS1202 && ZHSS1402) ||(ZHSS1202 && ZPEM1202) || (ZHSS1302 && ZHSS1304) || (ZHSS1302 && ZHSS1402) || (ZHSS1302 && ZPEM1202) || (ZHSS1304 && ZPEM1202) || (ZHSS1402 && ZPEM1202)"
			elif (codeInUrl[0] == "ZHSS2600"):
				#!!!
				normalised_pre_req = "(SCHOOL_APPROVAL)"
			#ZHSS3201 skipped
			#ZHSS3202 skipped
			elif (codeInUrl[0] == "ZHSS3231"):
				normalised_pre_req = "(ZHSS1201 || ZHSS1202 || SCHOOL_APPROVAL)"
			elif (codeInUrl[0] == "ZHSS3234"):
				normalised_pre_req = "(ZHSS1201 || ZHSS1202 || (ZHSS1401 && ZHSS1402))"
			elif (codeInUrl[0] == "ZHSS3421"):
				normalised_pre_req = "((ZHSS1401 || ZHSS1402 || ZHSS2600)"
			elif (codeInUrl[0] == "ZPEM2401"):
				#???
				normalised_pre_req = "((ZPEM1302 || ZPEM1304) && ZPEM2302 && ZPEM1501 && ZPEM1402)"
			elif (codeInUrl[0] == "ZPEM2502"):
				normalised_pre_req = "((ZPEM1301 || ZPEM1303) && (ZPEM1302 || ZPEM1304) && ZPEM1501 && ZPEM1502 && (ZPEM2302 || ZPEM2309))"
			elif (codeInUrl[0] == "ZPEM2506"):
				normalised_pre_req = "((ZPEM1301 || ZPEM1303) && (ZPEM1302 || ZPEM1304) && ZPEM1501 && (ZPEM1402 || ZPEM1502))"
			elif (codeInUrl[0] == "ZPEM2509"):
				normalised_pre_req = "((ZPEM1301 || ZPEM1303) && (ZPEM1302 || ZPEM1304) && ZPEM1501 && ZPEM1502)"
			elif (codeInUrl[0] == "ZPEM3103"):
				normalised_pre_req = "(ZPEM1301 && ZPEM1302 && (ZPEM2113 || ZPEM2502))"
			elif (codeInUrl[0] == "ZPEM3107"):
				normalised_pre_req = "((ZPEM2102 && ZPEM2113) || ZINT2501)"
			elif (codeInUrl[0] == "ZPEM3524"):
				normalised_pre_req = "((ZPEM2401 || ZPEM2502) && ZPEM2506)"


			#php explosion preparation
			normalised_pre_req = re.sub(r'\(', '( ', normalised_pre_req, flags=re.IGNORECASE)
			normalised_pre_req = re.sub(r'\)', ' )', normalised_pre_req, flags=re.IGNORECASE)

			

			f.write("INSERT INTO pre_reqs (course_code, title, uoc, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, original_pre_req_condition, normalised_pre_req, codeInUrl[0], career))
         
			#print normalised_pre_req[0].group()
		else:
			#print "went here"
			f.write("INSERT INTO pre_reqs (course_code, title, uoc, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))

		if (co_req_matches or normalised_co_req):

			if (co_req_matches):
				#print co_req_matches[0][0]
				#print "searching"
				#print co_req_sentence.search(co_req_matches[0][0]).group(0)
				#print "groups"

				normalised_co_req = co_req_sentence.search(co_req_matches[0][0]).group(0)
			#print subject_code_website
			#print "printing"
			#print normalised_co_req
				
			
			original_co_req_condition = normalised_co_req
			normalised_co_req = re.sub(r"\'", "\'\'", normalised_co_req, flags=re.IGNORECASE)
			original_co_req_condition = re.sub(r"\'", "\'\'", original_co_req_condition, flags=re.IGNORECASE)

			#remove normalised_co_req word
			normalised_co_req = re.sub(r"Co(.*?:|requisite) ?", "(", normalised_co_req, flags=re.IGNORECASE)

			#change to ands
			normalised_co_req = re.sub(r"\sAND\s", " && ", normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r"\s&\s", " && ", normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r"\sincluding\s", " && ", normalised_co_req, flags=re.IGNORECASE)

			#comma can mean and/or
			normalised_co_req = re.sub(r",\s*or", " || ", normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r",", " && ", normalised_co_req, flags=re.IGNORECASE)

			#change to ors
			normalised_co_req = re.sub(r';', ' || ', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\sOR\s', ' || ', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\/', ' || ', normalised_co_req, flags=re.IGNORECASE)

			#change to uoc
			normalised_co_req = re.sub(r'\s*uoc\b', '_UOC', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\s*uc\b', '_UOC', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', normalised_co_req, flags=re.IGNORECASE)

			#remove unnecessary words
			normalised_co_req = re.sub(r'\.', '', normalised_co_req, flags=re.IGNORECASE)

			#change [] to ()
			normalised_co_req = re.sub(r'\[', '(', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\]', ')', normalised_co_req, flags=re.IGNORECASE)

			normalised_co_req = re.sub(r'stream', '', normalised_co_req, flags=re.IGNORECASE)

			#cleanup
			normalised_co_req = re.sub(r'&&\s*&&$', '&&', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'&&\s*$', ' ', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\|\|\s*$', ' ', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req += ")"
			normalised_co_req = re.sub(r'\(\s*\)', ' ', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\(\s*', '(', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\s*\)', ')', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\s\s+', ' ', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\'', '\'\'', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'^\s+$', '', normalised_co_req, flags=re.IGNORECASE)

			#print "writing"

			#manual
			if (codeInUrl[0] == "ACTL4002"):
				normalised_co_req = "(ACTL3162 && ACTL3182)"
			elif (codeInUrl[0] == "ACTL4303"):
				normalised_co_req = ""
			elif (codeInUrl[0] == "ACTL5303"):
				normalised_co_req = "(ACTL5109)"
			elif (codeInUrl[0] == "AERO3640"):
				normalised_co_req = "(MMAN3200)"
			elif (codeInUrl[0] == "COMP3231"):
				normalised_co_req = "(COMP2121 && 75_WAM)"
			elif (codeInUrl[0] == "COMP3891"):
				normalised_co_req = "((COMP2121 || ELEC2142) && 75_WAM)"
			elif (codeInUrl[0] == "COMP4128" and career == "UG"):
				normalised_co_req = "(COMP3821 || (COMP3121 && 75_WAM)"
			elif (codeInUrl[0] == "ELEC2134"):
				normalised_co_req = "(ELEC1111 || ELEC1112)"
			elif (codeInUrl[0] == "ENGG0380"):
				normalised_co_req = "(FACULTY_ENGINEERING)"
			elif (codeInUrl[0] == "FINS5512"):
				normalised_co_req = "(ACCT5906 || ECON5103 || 9273 || 5273 || 7273 || 8007)"
			elif (codeInUrl[0] == "FINS5516" or codeInUrl[0] == "FINS5530" or codeInUrl[0] == "FINS5531" or codeInUrl[0] == "FINS5542"):
				normalised_co_req = "((FINS5513 || 8406)"
			elif (codeInUrl[0] == "FINS5522"):
				normalised_co_req = "((FINS5512 && FINS5513) || 8406)"
			elif (codeInUrl[0] == "FINS5533"):
				normalised_co_req = "(FINS5513 || FINS5561 || 8406)"
			elif (codeInUrl[0] == "FINS5566"):
				normalised_co_req = "(FINS5512 || 8406 || 8413)"
			elif (codeInUrl[0] == "FOOD9430"):
				normalised_co_req = "(24_UOC_LEVEL_3_4_FOOD)"
			#JURD skipped
			#LAWS skipped
			elif (codeInUrl[0] == "MARK5820"):
				normalised_co_req = "(MARK5800 || MARK5801 || (7291 || 5291 || 8291 || 8281))"
			elif (codeInUrl[0] == "MATS5003"):
				normalised_co_req = "(MATS5003)"
			elif (codeInUrl[0] == "MGMT5603" or codeInUrl[0] == "MGMT5604"):
				normalised_co_req = "(IBUS5601 || MGMT5601)"
			#MNGT6274 skipped
			#MNGT6372 skipped
			elif (codeInUrl[0] == "OPTM4271"):
				normalised_co_req = "(OPTM4211 && OPTM4231 && OPTM4251)"
			elif (codeInUrl[0] == "PHYS3080"):
				normalised_co_req = "((PHYS3010 || PHYS3210) && PHYS3020)"
			#ZHSS3201 skipped
			#ZHSS3202 skipped

			#php explosion preparation
			normalised_co_req = re.sub(r'\(', '( ', normalised_co_req, flags=re.IGNORECASE)
			normalised_co_req = re.sub(r'\)', ' )', normalised_co_req, flags=re.IGNORECASE)



			g.write("INSERT INTO co_reqs (course_code, title, uoc, career, co_req_conditions, norm_co_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, original_co_req_condition, normalised_co_req, codeInUrl[0], career))
         
			#print normalised_pre_req[0].group()
		else:
			#print "went here"
			g.write("INSERT INTO co_reqs (course_code, title, uoc, career, co_req_conditions, norm_co_req_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))

		if normalised_equivalence:
			#print normalised_equivalence
			original_equivalence_condition = normalised_equivalence
			normalised_equivalence = re.sub(r"\'", "\'\'", normalised_equivalence, flags=re.IGNORECASE)
			original_equivalence_condition = re.sub(r"\'", "\'\'", original_equivalence_condition, flags=re.IGNORECASE)

			#remove normalised_equivalence word
			normalised_equivalence = re.sub(r"[eE]quiv.*?:", "(", normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r"<\/strong>&nbsp;", " ", normalised_equivalence, flags=re.IGNORECASE)

			#change to ands
			normalised_equivalence = re.sub(r"\sAND\s", " || ", normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r"\s&\s", " || ", normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r"\sincluding\s", " || ", normalised_equivalence, flags=re.IGNORECASE)

			#comma can mean and/or
			normalised_equivalence = re.sub(r",\s*or", " || ", normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r",", " || ", normalised_equivalence, flags=re.IGNORECASE)

			#change to ors
			normalised_equivalence = re.sub(r';', ' || ', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\sOR\s', ' || ', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\/', ' || ', normalised_equivalence, flags=re.IGNORECASE)

			#change to uoc
			normalised_equivalence = re.sub(r'\s*uoc\b', '_UOC', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\s*uc\b', '_UOC', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', normalised_equivalence, flags=re.IGNORECASE)

			#remove unnecessary words
			normalised_equivalence = re.sub(r'\.', '', normalised_equivalence, flags=re.IGNORECASE)

			#change [] to ()
			normalised_equivalence = re.sub(r'\[', '(', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\]', ')', normalised_equivalence, flags=re.IGNORECASE)



			normalised_equivalence = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'approval from the School', "SCHOOL_APPROVAL", normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'school approval', "SCHOOL_APPROVAL", normalised_equivalence, flags=re.IGNORECASE)
			#normalised_equivalence = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'Enrolment in program ([0-9]+)', r'\1', normalised_equivalence, flags=re.IGNORECASE)

			#normalised_equivalence = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', 
				#"(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", normalised_equivalence, flags=re.IGNORECASE)
			#normalised_equivalence = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s*[^a-zA-Z0-9]*$', r'\1_UOC_LEVEL_\2', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s+([A-Za-z])', r'\1_UOC_LEVEL_\2_\3', normalised_equivalence, flags=re.IGNORECASE)

			normalised_equivalence = re.sub(r'stream', '', normalised_equivalence, flags=re.IGNORECASE)

			#cleanup
			normalised_equivalence = re.sub(r'&&\s*&&$', '&&', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'&&\s*$', ' ', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\|\|\s*$', ' ', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence += ")"
			normalised_equivalence = re.sub(r'\(\s*\)', ' ', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\(\s*', '(', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\s*\)', ')', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\s\s+', ' ', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\'', '\'\'', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'^\s+$', '', normalised_equivalence, flags=re.IGNORECASE)

			#php explosion preparation
			normalised_equivalence = re.sub(r'\(', '( ', normalised_equivalence, flags=re.IGNORECASE)
			normalised_equivalence = re.sub(r'\)', ' )', normalised_equivalence, flags=re.IGNORECASE)

			#print normalised_equivalence
			

			h.write("INSERT INTO equivalence (course_code, title, uoc, career, equivalence_conditions, norm_equivalence_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM equivalence WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, original_equivalence_condition, normalised_equivalence, codeInUrl[0], career))
         
			#print normalised_pre_req[0].group()
		else:
			#print "went here"
			h.write("INSERT INTO equivalence (course_code, title, uoc, career, equivalence_conditions, norm_equivalence_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM equivalence WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))

		if normalised_exclusion:
			#print normalised_exclusion
			original_exclusion_condition = normalised_exclusion
			normalised_exclusion = re.sub(r"\'", "\'\'", normalised_exclusion, flags=re.IGNORECASE)
			original_exclusion_condition = re.sub(r"\'", "\'\'", original_exclusion_condition, flags=re.IGNORECASE)

			#remove normalised_exclusion word
			normalised_exclusion = re.sub(r"[eE]xcl.*?:", "(", normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r"<\/strong>&nbsp;", " ", normalised_exclusion, flags=re.IGNORECASE)

			#change to ands
			normalised_exclusion = re.sub(r"\sAND\s", " || ", normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r"\s&\s", " || ", normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r"\sincluding\s", " || ", normalised_exclusion, flags=re.IGNORECASE)

			#comma can mean and/or
			normalised_exclusion = re.sub(r",\s*or", " || ", normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r",", " || ", normalised_exclusion, flags=re.IGNORECASE)

			#change to ors
			normalised_exclusion = re.sub(r';', ' || ', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\sOR\s', ' || ', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\/', ' || ', normalised_exclusion, flags=re.IGNORECASE)

			#change to uoc
			normalised_exclusion = re.sub(r'\s*uoc\b', '_UOC', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\s*uc\b', '_UOC', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', normalised_exclusion, flags=re.IGNORECASE)

			#remove unnecessary words
			normalised_exclusion = re.sub(r'\.', '', normalised_exclusion, flags=re.IGNORECASE)

			#change [] to ()
			normalised_exclusion = re.sub(r'\[', '(', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\]', ')', normalised_exclusion, flags=re.IGNORECASE)



			normalised_exclusion = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'approval from the School', "SCHOOL_APPROVAL", normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'school approval', "SCHOOL_APPROVAL", normalised_exclusion, flags=re.IGNORECASE)
			#normalised_exclusion = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'Enrolment in program ([0-9]+)', r'\1', normalised_exclusion, flags=re.IGNORECASE)

			#normalised_exclusion = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', 
				#"(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", normalised_exclusion, flags=re.IGNORECASE)
			#normalised_exclusion = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s*[^a-zA-Z0-9]*$', r'\1_UOC_LEVEL_\2', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s+([A-Za-z])', r'\1_UOC_LEVEL_\2_\3', normalised_exclusion, flags=re.IGNORECASE)

			normalised_exclusion = re.sub(r'stream', '', normalised_exclusion, flags=re.IGNORECASE)

			#cleanup
			normalised_exclusion = re.sub(r'&&\s*&&$', '&&', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'&&\s*$', ' ', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\|\|\s*$', ' ', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion += ")"
			normalised_exclusion = re.sub(r'\(\s*\)', ' ', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\(\s*', '(', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\s*\)', ')', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\s\s+', ' ', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\'', '\'\'', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'^\s+$', '', normalised_exclusion, flags=re.IGNORECASE)

			

			#print normalised_exclusion
			if (codeInUrl[0] == "ARTS1480"):
				normalised_exclusion = "(FREN1000 || FREN1101 || GENT0425)"
			elif (codeInUrl[0] == "ARTS1481"):
				normalised_exclusion = "(FREN1002 || FREN1102)"
			elif (codeInUrl[0] == "ARTS2480" or codeInUrl[0] == "ARTS2481"):
				normalised_exclusion = ""
			elif (codeInUrl[0] == "BIOM2451"):
				normalised_exclusion = "(FACULTY_ENGINEERING)"
			elif (codeInUrl[0] == "CEIC4001" or codeInUrl[0] == "CEIC4002" or codeInUrl[0] == "CHEN6710"):
				normalised_exclusion = "(CEIC4005)"
			elif (codeInUrl[0] == "CEIC4003"):
				normalised_exclusion = "(CEIC4005 || CEIC4006)"
			elif (codeInUrl[0] == "CEIC4005"):
				normalised_exclusion = "(CEIC4002 || CEIC4003)"
			elif (codeInUrl[0] == "CEIC4006"):
				normalised_exclusion = "(CEIC4003 || CEIC4005)"
			elif (codeInUrl[0] == "COMP4931"):
				normalised_exclusion = "(4515)"
			elif (codeInUrl[0] == "CRIM2020"):
				normalised_exclusion = "(FACULTY_LAW)"
			elif (codeInUrl[0] == "ECON1203"):
				normalised_exclusion = "(MATH2841 || MATH2801 || MATH2901 || MATH2099 || ACTL2002)"
			elif (re.match('GENC', codeInUrl[0])):
				normalised_exclusion = "(FACULTY_BUSINESS)"
			elif (re.match('GENM', codeInUrl[0])):
				normalised_exclusion = "(FACULTY_MEDICINE)"
			elif (re.match('GENT', codeInUrl[0])):
				normalised_exclusion = "(FACULTY_ARTS)"
			elif (codeInUrl[0] == "IEST6907"):
				normalised_exclusion = "(3988 || 3932)"
			elif (codeInUrl[0] == "JURD7321"):
				normalised_exclusion = "(JURD7446 || JURD7448 || JURD7617)"
			elif (codeInUrl[0] == "JURD7446" or codeInUrl[0] == "JURD7448"):
				normalised_exclusion = "(JURD7321 || JURD7617)"
			elif (codeInUrl[0] == "JURD7617"):
				normalised_exclusion = "(JURD7321)"
			elif (codeInUrl[0] == "MATH2089"):
				normalised_exclusion = "(CVEN2002 || CVEN2025 || CVEN2702 || ECON3209 || MATH2049 || MATH2829 || MATH2839 || MATH2899 || MINE2700)"
			elif (codeInUrl[0] == "MATH2301"):
				normalised_exclusion = "(MATH2089 || CVEN2002 || CVEN2702)"
			elif (codeInUrl[0] == "MSCI0501"):
				normalised_exclusion = "(GENS4625 || MSCI2001 || GENB5001 || FACULTY_SCIENCE)"
			elif (codeInUrl[0] == "PHYS4979"):
				normalised_exclusion = "(PHYS3780 || !(3644))"

			#php explosion preparation
			normalised_exclusion = re.sub(r'\(', '( ', normalised_exclusion, flags=re.IGNORECASE)
			normalised_exclusion = re.sub(r'\)', ' )', normalised_exclusion, flags=re.IGNORECASE)


			

			i.write("INSERT INTO exclusion (course_code, title, uoc, career, exclusion_conditions, norm_exclusion_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM exclusion WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, original_exclusion_condition, normalised_exclusion, codeInUrl[0], career))
         
			#print normalised_pre_req[0].group()
		else:
			#print "went here"
			i.write("INSERT INTO exclusion (course_code, title, uoc, career, exclusion_conditions, norm_exclusion_conditions) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM exclusion WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))


		if subject_area:
			subject_area = re.sub(r"\'", "\'\'", subject_area, flags=re.IGNORECASE)

			j.write("INSERT INTO subject_area (course_code, title, uoc, career, area) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM subject_area WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, subject_area, codeInUrl[0], career))
		else:
			j.write("INSERT INTO subject_area (course_code, title, uoc, career, area) SELECT \'%s\', \'%s\', \'%d\', \'%s\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM subject_area WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], title, uoc, career, codeInUrl[0], career))

	except:
		print title
		print uoc
		print codeInUrl,
		print "No Handbook Entry"
		#normalised_pre_req = "WARNING"
		#normalised_co_req = "WARNING"
		#normalised_equivalence = "WARNING"
		#if original_pre_req_condition:
		#	f.write("INSERT INTO pre_reqs (course_code, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, original_pre_req_condition, normalised_pre_req, codeInUrl[0], career))
		#if original_co_req_condition:
	#		g.write("INSERT INTO co_reqs (course_code, career, co_req_conditions, norm_co_req_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, original_co_req_condition, normalised_co_req, codeInUrl[0], career))
		#if original_equivalence_condition:
		#	h.write("INSERT INTO normalised_equivalence (course_code, career, equivalence_conditions, norm_equivalence_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM co_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, original_equivalence_condition, normalised_equivalence, codeInUrl[0], career))


f.close()
g.close()
h.close()
i.close()