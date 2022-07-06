GOOS=linux GOARCH=amd64 go build -ldflags="-s -w" -o while while.go
GOOS=darwin GOARCH=amd64 go build -ldflags="-s -w" -o while_darwin while.go
GOOS=windows GOARCH=amd64 go build -ldflags="-s -w" -o while.exe while.go
upx -9 while
