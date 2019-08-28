#enter participant id below
participantid<-1987
#enter the folder in which the data was saved below (likely downloads)
whereisit<-"/home/hanshalbe/Downloads/"
#enter the folder in which you want to store the data below
wheredoiwantit<-"/home/hanshalbe/sadsearch/experiment/data/"

#need jsonlite library
library(jsonlite)

#SAVE QUESTIONAIRE DATA
mquest<-fromJSON(paste0(whereisit,"gridSearch.survey.", participantid, ".JSON"))
ffi<-as.numeric(unlist(mquest$ffi$`Bitte bewerten Sie die folgenden Aussagen`))
shaps<-as.numeric(mquest$shaps)
bdi<-mquest$bdi
age<-as.numeric(mquest$age)
gender<-ifelse(mquest$gender==2, "male", "female")

dquest<-data.frame(id=rep(participantid, 95),
                   gender=rep(gender, 95),
                   age=rep(age, 95),
                   questionaire=c(rep("FFI", 60), rep("SHAPS", 14), rep("BDI", 21)),
                   questionnumber=c(1:60, 1:14, 1:21),
                   answer=c(ffi, shaps, bdi))


write.csv(dquest, paste0(wheredoiwantit, "surveydata",  participantid, ".csv"))


#SAVE GRID DATA
mgrid<-fromJSON(paste0(whereisit,"gridSearch.gridData.", participantid, ".JSON"))

time<-as.vector(t(mgrid$searchHistory$tscollect))
x<-as.vector(t(mgrid$searchHistory$xcollect))
y<-as.vector(t(mgrid$searchHistory$ycollect))
z<-as.vector(t(mgrid$searchHistory$zcollect))
gscale<-rep(mgrid$scale, each=26)
environ<-rep(mgrid$envOrder, each=26)
stars<-rep(mgrid$starArray, each=26)

dgrid<-data.frame(id=rep(participantid, 260),
                  round=rep(1:10, each=26),
                  trial=rep(1:26, 10),
                  x, y, z, time, gscale, environ, stars)

write.csv(dgrid, paste0(wheredoiwantit, "gridsearchdata",  participantid, ".csv"))

#SAVE BONUS ROUND DATA
dbonus<-mgrid$bonusLevel$bonusCells
dbonus$chosen<-0
dbonus$chosen[dbonus$x==mgrid$bonusLevel$finalChosenCell$x & dbonus$y==mgrid$bonusLevel$finalChosenCell$y]<-1

write.csv(dbonus, paste0(wheredoiwantit, "bonusrounddata",  participantid, ".csv"))
