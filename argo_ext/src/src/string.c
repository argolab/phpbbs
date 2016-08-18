#include "ext_prototype.h"

/*
 * modified from ansi_ctrl, example:
 * from: "\033[m...", string to be analysed
 * outbuf: font style "<font class=\"c37\">",
 * bufsize(>= 26, length of "</font><font class=\"c37\">") for safety,
 * offset(the length of seq) should be specific nomatter success or not.
 */

int html_print_ansi_seq(const char *from, int *ref_offset, int *gfont, int *nfont) {

	int c, i;
	char *ptr;
	char ansibuf[20];

	if (*(from + 1) != '[') {
		*ref_offset = 1;
		return 0;
	}
	for (i = 2; from[i]; i++) {
		if (strchr("0123456789;", from[i]) == 0) break;
	}
	*ref_offset = i + 1;

	if (i > 16 || from[i] != 'm') return 0;

	snprintf(ansibuf, i, "%s",  from + 2);
	ptr = strtok(ansibuf, ";");
	while (ptr) {
		c = atoi(ptr);
		if(!strcmp(ptr, "m")) {
            if(*nfont) php_printf("</font>"), *nfont--;
			php_printf("<font class=\"c37\">");
            *nfont ++;
		} else if (c >= 30 && c <= 37) {
            if(*nfont) php_printf("</font>"), *nfont--;
			php_printf("<font class=\"c%d\">",c);
            *nfont ++;
		} else if( c == 0) {
            while(*nfont) php_printf("</font>"), *nfont--;
        }
		ptr = strtok(NULL, ";");
	}
	return 1;
}


int html_print_buffer(char *from, int size)
{
	int len;
	int pos = 0;    
	int gfont=0,nfont = 0; //nfont: number of normal font, gfont: number of gray font

	while (pos < size) {

        if(gfont + nfont == 0) php_printf("<font class=\"c37\">"), nfont++;
        
		if (from[pos] == 27) {
			html_print_ansi_seq(&from[pos], &len, &gfont, &nfont);
			pos += len;
		} else if (from[pos] < 0) { /* Êä³öºº×Ö */
			if (from[pos + 1]) {
				php_write(&from[pos], 2);
				pos += 2;
			} else {		/* µ¥×Ö·ûºº×ÖºöÂÔ */
				pos++;
			}

		} else {					 /* ´¦ÀíÆäËü×Ö×Ö·û */
			if (from[pos] == '\n') { /* »»ÐÐ */
                    //»»ÐÐÇåµôËùÓÐ»ÒÉ«font
                while(gfont) php_printf("</font>"),gfont--;
                
				if (!strncmp(&from[pos + 1], ": ", 2)) {
					php_printf("<br /><font class=\"c30\">");
                    gfont ++;
				} else {
					php_printf("<br />");
				}
			} else if (from[pos] == '<') {
				php_write("&lt;", 4);
			} else if (from[pos] == '>') {
				php_write("&gt;", 4);
			} else if (from[pos] == '&') {
				php_write("&amp;", 5);
			} else if (from[pos] == ' ') {
				php_write("&nbsp;", 6);
			} else if(from[pos] == '"'){
                php_write("&quot;", 6);                
            } else if(from[pos] == '\'') {
                php_write("&#039;", 6);
            } else {
				php_write(&from[pos], 1);
			}
			pos++;
		}
	}
    nfont += gfont;
	while(nfont)  php_printf("</font>"),nfont--;
	return 1;
}

/**
  int html_print_ansi_seq(const char *from, int *ref_offset) {

	int c, i;
	char *ptr;
	char ansibuf[20];

	if (*(from + 1) != '[') {
		*ref_offset = 1;
		return 0;
	}
	for (i = 2; from[i]; i++) {
		if (strchr("0123456789;", from[i]) == 0) break;
	}
	*ref_offset = i + 1;

	if (i > 16 || from[i] != 'm') return 0;

	snprintf(ansibuf, i, "%s",  from + 2);
	ptr = strtok(ansibuf, ";");
	while (ptr) {
		c = atoi(ptr);
		if(!strcmp(ptr, "m")) {
			php_printf("</font><font class=\"c37\">");
		} else if (c >= 30 && c <= 37) {
			php_printf("</font><font class=\"c%d\">",c);
		}
		ptr = strtok(NULL, ";");
	}
	return 1;
}


int html_print_buffer(char *from, int size)
{
	int len;
	int pos = 0;
	int infont = 0;
	if (!strncmp(&from[pos], ": ", 2)) {
		php_printf("<font class=\"c30\">");
	} else {
		php_printf("<font class=\"c37\">");
	}
	while (pos < size) {
        
		if (from[pos] == 27) {
			html_print_ansi_seq(&from[pos], &len);
			pos += len;
		} else if (from[pos] < 0) { // Êä³öºº×Ö 
			if (from[pos + 1]) {
				php_write(&from[pos], 2);
				pos += 2;
			} else {		// µ¥×Ö·ûºº×ÖºöÂÔ 
				pos++;
			}

		} else {					 // ´¦ÀíÆäËü×Ö×Ö·û 
			if (from[pos] == '\n') { 
				if (!strncmp(&from[pos + 1], ": ", 2)) {
					php_write("</font><br /><font class=\"c30\">", 31);
				} else {
					php_write("<br />", 6);
				}
			} else if (from[pos] == '<') {
				php_write("&lt;", 4);
			} else if (from[pos] == '>') {
				php_write("&gt;", 4);
			} else if (from[pos] == '&') {
				php_write("&amp;", 5);
			} else if (from[pos] == ' ') {
				php_write("&nbsp;", 6);
			} else {
				php_write(&from[pos], 1);
			}
			pos++;
		}
	}
	php_printf("</font>", 7);
	return 1;
}

 **/

/* borrow from script.c in old websrc */
char *safe_strcat(char *str1, const char *str2, int catlen, int *len)
{
	if (catlen == 0) catlen = strlen(str2);
	if (catlen > *len) {
		len = 0;
		return NULL;
	}
	*len -= catlen;
	strncat(str1, str2, catlen);
	return str1;
}

/* assert ( the converted string size < 1024 ) */
char *nohtml(char *s)
{
	static char buf[1024];
	int i = 0;
	while (s[0] && i < 1000) {
		if(s[0]=='<') {
			strcpy(buf + i, "&lt;");
			i += 4;
		} else if(s[0] == '>') {
			strcpy(buf + i, "&gt;");
			i += 4;
		} else if(s[0] == '"') {
			strcpy(buf + i, "&quot;");
			i += 6;
		} else if(s[0] == '&') {
			strcpy(buf + i, "&amp;");
			i += 5;
		} else {
			buf[i] = s[0];
			i++;
		}
		s++;
	}
	buf[i] = 0;
	return buf;
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */

