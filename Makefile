CC=g++ -o scripts/$@ scripts/$@.cpp

all: testBED status interval binUnsorted CNVcaller

testBED: scripts/testBED.cpp
	$(CC)

status: scripts/status.cpp
	$(CC)

interval: scripts/interval.cpp
	$(CC)

binUnsorted: scripts/binUnsorted.cpp
	$(CC)

CNVcaller: scripts/CNVcaller.cpp
	$(CC)

