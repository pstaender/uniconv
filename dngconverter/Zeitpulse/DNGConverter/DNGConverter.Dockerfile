FROM scottyhardy/docker-wine:latest
# Install manually Adobes DNG Converter

RUN wget https://www.zeitpulse.com/AdobeDNGConverter_x64_13_3.tar.gz -O AdobeDNGConverter_x64_13_3.tar.gz
RUN tar -xvf AdobeDNGConverter_x64_13_3.tar.gz
RUN find AdobeDNGConverter_x64_13_3/ -type f -name '._*' -delete
RUN mv AdobeDNGConverter_x64_13_3 /usr/share/AdobeDNGConverter_x64_13_3

# Create the user account (copied -> https://github.com/scottyhardy/docker-wine/blob/master/entrypoint.sh)
ENV USER_NAME=wineuser
ENV USER_UID=1010
ENV USER_GID=1010
ENV USER_HOME=/home/wineuser
RUN ! grep -q ":${USER_GID}:$" /etc/group && groupadd --gid "${USER_GID}" "${USER_NAME}"
RUN useradd --shell /bin/bash --uid "${USER_UID}" --gid "${USER_GID}" --password "${USER_PASSWD}" --no-create-home --home-dir "${USER_HOME}" "${USER_NAME}"
# Create the user's home if it doesn't exist
RUN [ ! -d "${USER_HOME}" ] && mkdir -p "${USER_HOME}"
RUN chown -R "${USER_UID}":"${USER_GID}" "${USER_HOME}"

# copy / symlink dng convert to wine drive
RUN mkdir -p ~/.wine/drive_c/Program\ Files/Adobe/ ~/.wine/drive_c/ProgramData/Adobe/
RUN ln -s /usr/share/AdobeDNGConverter_x64_13_3/Adobe\ DNG\ Converter/ ~/.wine/drive_c/Program\ Files/Adobe/
RUN ln -s /usr/share/AdobeDNGConverter_x64_13_3/CameraRaw/ ~/.wine/drive_c/ProgramData/Adobe
RUN ln -s /convertfiles ~/.wine/drive_c/

# run initially; `2>&1 || true` forces an exit code 0, so that the docker build is not aborted
RUN wine ~/.wine/drive_c/Program\ Files/Adobe/Adobe\ DNG\ Converter/Adobe\ DNG\ Converter.exe -c -p0 C:\\a_file_that_not_exists 2>&1 || true

RUN mv ~/.wine /home/wineuser
RUN chown -R "${USER_UID}":"${USER_GID}" /home/wineuser/.wine
# ENV WINEPREFIX=/wine

# wine ~/.wine/drive_c/Program\ Files/Adobe/Adobe\ DNG\ Converter/Adobe\ DNG\ Converter.exe -c -p0 C:\\converter\\IMG_4253.CR2
