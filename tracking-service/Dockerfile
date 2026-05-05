FROM golang:1.22-alpine

WORKDIR /app

# Copia os arquivos de dependência e baixa
COPY go.mod go.sum ./
RUN go mod download

# Copia o código fonte
COPY *.go ./

# Compila o binário
RUN go build -o main .

# Expõe a porta do WebSockets
EXPOSE 8080

# Roda o serviço
CMD ["./main"]