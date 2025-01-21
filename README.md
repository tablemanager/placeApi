
## 프로젝트 README

### 개요

이 프로젝트는 Apache와 PHP 7.4를 기반으로 `api_html` 폴더에 배치된 PHP 애플리케이션을 Docker 컨테이너에서 실행하기 위한 환경을 설정합니다. Docker와 Docker Compose를 사용하여 간편하게 개발 및 배포 환경을 설정할 수 있도록 구성되었습니다.
실 적용시 000-default.conf 파일의 폴더 위치 수정해야 됩니다. 

---

### 구성 파일

#### 1. **Dockerfile**
- **PHP 7.4와 Apache가 포함된 기본 이미지**를 사용합니다.
- Apache 설정(`000-default.conf`)을 컨테이너에 복사합니다.
- 프로젝트 폴더(`api_html`)를 `/var/www/html` 디렉토리로 복사합니다.
- Apache 모듈 활성화:
  - `mod_rewrite`
  - `mod_headers`
- 포트 **80**을 Docker 컨테이너에서 노출합니다.
- PHP 확장 `mysqli` 설치(`docker-php-ext-install` 사용).

---

#### 2. **docker-compose.yml**
- **서비스 이름: `api_html`**
  - Dockerfile을 기반으로 이미지를 빌드합니다.
  - 컨테이너 이름: `api_html_container`
  - 호스트의 80번 포트를 컨테이너의 80번 포트에 매핑합니다.
  - 볼륨:
    - `api_html` 폴더를 Apache의 루트 디렉토리(`/var/www/html`)로 연결하여 파일 변경 사항이 즉시 반영됩니다.
    - `000-default.conf` 파일을 컨테이너 내 Apache 설정 디렉토리로 연결합니다.
  - 환경 변수:
    - Apache 로그 디렉토리: `/var/log/apache2`
  - 컨테이너가 종료될 경우, 자동으로 재시작(`restart: always`).

---

### 요구 사항

- Docker 설치 ([공식 설치 가이드](https://docs.docker.com/get-docker/))
- Docker Compose 설치 ([공식 설치 가이드](https://docs.docker.com/compose/install/))

---

### 사용법

1. **프로젝트 클론**
   ```bash
   git clone [REPOSITORY_URL]
   cd [PROJECT_DIRECTORY]
   ```

2. **Docker 컨테이너 빌드 및 실행**
   ```bash
   docker-compose up --build
   ```

3. **브라우저에서 확인**
   - 로컬호스트에서 애플리케이션 실행 확인: [http://localhost](http://localhost)
   - Apache 설정에 따라 추가 도메인이 필요하면 `/etc/hosts` 파일에 다음과 같이 추가합니다:
     ```
     127.0.0.1 gateway.ticketmanager.ai
     ```

4. **컨테이너 중지**
   ```bash
   docker-compose down
   ```

---

### 디렉토리 구조

```
project/
├── api_html/                  # PHP 파일이 위치하는 디렉토리
│   └── index.php              # 예시 PHP 파일
├── 000-default.conf           # Apache 설정 파일
├── Dockerfile                 # Docker 단독 빌드용 설정
└── docker-compose.yml         # Docker Compose 설정
```

---

### 주요 명령어

1. **컨테이너 빌드 및 실행**
   ```bash
   docker-compose up --build
   ```

2. **백그라운드에서 실행**
   ```bash
   docker-compose up -d
   ```

3. **컨테이너 중지 및 정리**
   ```bash
   docker-compose down
   ```

4. **로그 확인**
   ```bash
   docker-compose logs
   ```

5. **컨테이너 내부 접근**
   ```bash
   docker exec -it api_html_container bash
   ```

---

### 문제 해결

1. **`Forbidden` 오류 발생 시**
   - `api_html` 디렉토리의 읽기/쓰기 권한 확인:
     ```bash
     sudo chmod -R 755 ./api_html
     sudo chown -R $USER:$USER ./api_html
     ```

2. **파일 변경 사항이 반영되지 않을 경우**
   - `docker-compose.yml`의 `volumes` 설정을 확인하고 제대로 마운트되었는지 테스트합니다:
     ```bash
     docker exec -it api_html_container ls /var/www/html
     ```

3. **Apache 또는 PHP 설정 문제**
   - 컨테이너 내부에서 로그 확인:
     ```bash
     docker logs api_html_container
     ```

---

이 README는 프로젝트 및 Docker 설정 파일을 기반으로 작성되었으며, 추가 수정이 필요하거나 질문이 있다면 알려주세요!
