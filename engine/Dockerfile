# Imagem multi-arch (amd64 e arm64), pronta para a VM A1 da OCI
FROM python:3.12-slim

# git para clonar repositorios, gh para abrir PR, node para testar projetos JS
RUN apt-get update \
 && apt-get install -y --no-install-recommends git curl ca-certificates gnupg nodejs npm \
 && curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg \
      -o /usr/share/keyrings/githubcli-archive-keyring.gpg \
 && echo "deb [signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" \
      > /etc/apt/sources.list.d/github-cli.list \
 && apt-get update && apt-get install -y --no-install-recommends gh \
 && rm -rf /var/lib/apt/lists/*

RUN useradd -m -u 1000 motor
WORKDIR /app

COPY pyproject.toml ./
COPY app ./app
RUN pip install --no-cache-dir .

RUN mkdir -p /dados /trabalho && chown motor:motor /dados /trabalho
USER motor
ENV MOTOR_BANCO=/dados/motor.sqlite
VOLUME ["/dados", "/trabalho"]
EXPOSE 8000
CMD ["uvicorn", "app.api:app", "--host", "0.0.0.0", "--port", "8000"]
