#include <stdio.h>
int main(int argc,char** argv)
    {
    int N=0;
    int T=0;
    for(;;)
        {
        int c=fgetc(stdin);
        switch(c)
            {
            case EOF: case '>':
                {
                if(T>0) printf("\t%d\t%d\t%f\n",N,T,N/(double)T);
                if(c==EOF) return 0;
                N=0;
                T=0;
                while((c=fgetc(stdin))!=EOF && c!='\n')
                    {
                    fputc(c,stdout);
                    }
                fputc('\t',stdout);
                break;
                }
            case ' ':case '\n': case '\r': break;
            case 'N': case 'n': ++N;/* continue */
            default: ++T;
            }
        }
    return 0;
    }

