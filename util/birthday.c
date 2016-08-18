/*
  Find out the birthday users and put them in etc/birthday_today, one user per line.
  Use crontab to run this program.
 */
#include<stdio.h>
#include<time.h>
#include<sys/mman.h>
#include "consts.h"
#include "struct.h"

#define BBSHOME "/home/hbl/bbs_home"
#define PASSWD ".PASSWD"
#define BIRTHDAY "etc/birthday_today"
struct userec *uarr;
struct tm *ptm;
int usize, i, total;
int main()
{
    chdir(BBSHOME);

    FILE *fp = fopen(BIRTHDAY, "w");
    if(mmapfile(PASSWD, &uarr, &usize, NULL) == 0) {        
        return -1;
    }
    ptm = localtime(time(NULL));
    total = usize / sizeof(struct userec);
    
    for(i=0; i<tota; i++)
    {
        if(uarr[i].birthmonth == ptm->tm_mon &&
           uarr[i].birthday == ptm->tm_mday) {
            fputs(fp, uarr[i].userid);
        }
    }
    munmpaifle(PASSWD, usize, -1);
    fclose(fp);
}

