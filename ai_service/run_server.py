from contextlib import redirect_stderr, redirect_stdout
import traceback

import uvicorn

from config import BASE_DIR, HOST, PORT


def main() -> None:
    log_path = BASE_DIR.parent / "storage" / "logs" / "ai-service-idm-vton-python.log"
    log_path.parent.mkdir(parents=True, exist_ok=True)

    try:
        with log_path.open("a", encoding="utf-8") as log_file:
            with redirect_stdout(log_file), redirect_stderr(log_file):
                uvicorn.run(
                    "main:app",
                    host=HOST,
                    port=PORT,
                    log_level="warning",
                    log_config=None,
                    access_log=False,
                    http="h11",
                )
    except BaseException:
        with log_path.open("a", encoding="utf-8") as log_file:
            log_file.write(traceback.format_exc())
        raise


if __name__ == "__main__":
    main()
