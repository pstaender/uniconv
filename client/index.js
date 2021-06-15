const commandLineArgs = require("command-line-args");
const commandLineUsage = require("command-line-usage");
const fs = require("fs");
const axios = require("axios");

const options = commandLineArgs(
  [
    { name: "targetFormat", defaultOption: true },
    { name: "vv", type: Boolean },
    { name: "verbose", alias: "v", type: Boolean },
  ],
  { stopAtFirstUnknown: true }
);

const accessToken = process.env["UNICONV_ACCESSTOKEN"];
const baseURL = process.env["UNICONV_BASEURL"] || "http://127.0.0.1:8080";

const argv = options._unknown || [];
const verbosity = options.verbose ? 1 : options.vv ? 2 : 0;

const packageJson = require('./package.json');

console.log(`uniconv v${packageJson.version}`);

const log = (msg, icon = "➡️", writer = null) => {
  let output =
    (icon || "") +
    "\t" +
    new Date().toLocaleTimeString(Intl.DateTimeFormat().resolvedOptions().locale) +
    ": " +
    msg;
  if (writer === null) {
    console.log(output);
  } else if (writer === "console.error") {
    console.error(output);
  } else if (writer === "process.stdout.write") {
    process.stdout.write(output);
  }
};

const showHelp = () => {
  const sections = [
    {
      // header: welcomeScreen,
      content: "Converts a file from one format to another",
    },
    {
      header: "Options",
      optionList: [
        {
          name: "vv",
          // typeLabel: '{underline file}',
          type: Boolean,
          description: "very verbose",
        },
        {
          name: "verbose",
          alias: "v",
          type: Boolean,
          description: "verbose",
        },
      ],
    },
    {
      header: "Synopsis",
      content: [
        {
          desc: "$ uniconv $targetFileExtension $file",
          example: "",
        },
        {
          desc: "$ uniconv mp3 myvideo.mp4",
          example: "Converts mp4 video to mp3 audio",
        },
      ],
    },
  ];
  console.log(commandLineUsage(sections));
};

const displayErrorMessage = (msg) => log(`Error: ${msg}`, "❌", "console.error");

const displayErrorMessageAndExit = (msg) => {
  displayErrorMessage(msg);
  process.exit(1);
};

const file = argv[0];
const { targetFormat } = options;

if (!targetFormat || !file) {
  showHelp();
  displayErrorMessageAndExit("Please provide all required arguments\n");
}

if (!fs.existsSync(file)) {
  displayErrorMessageAndExit(`The file '${file}' does not exists`);
}

if (!accessToken) {
  displayErrorMessageAndExit(
    `No accesstoken provided. Set via env with name CONVERTER_ACCESSTOKEN`
  );
}

const handleError = (e, exitAfter) => {
  let msg = e.message;
  let userFriendlyErrorMessage = null;
  if (e.response && e.response.data && e.response.data.error) {
    userFriendlyErrorMessage = e.response.data.error;
  }
  let errorMessage = msg;

  if (verbosity > 0) {
    if (verbosity > 1) {
      console.error(e);
    }
    if (userFriendlyErrorMessage) {
      msg += ": " + userFriendlyErrorMessage;
    }

    if (e.response && e.response.data) {
      console.error(e.response.data);
    }

    errorMessage = msg || e.message;
  } else {
    errorMessage = userFriendlyErrorMessage || msg;
  }
  if (exitAfter) {
    displayErrorMessageAndExit(errorMessage);
  } else {
    displayErrorMessage(errorMessage);
  }
}

const fileSizeInMB = (file) => Math.floor((fs.statSync(file).size / 1024 / 1024) * 100) / 100

const convertFile = async (
  baseURL,
  accessToken,
  targetFormat,
  file,
  targetFile = null
) => {
  async function downloadFile(client, url, targetFile) {
    const writer = fs.createWriteStream(targetFile);

    const response = await client({
      url,
      method: "GET",
      responseType: "stream",
    });

    response.data.pipe(writer);

    return new Promise((resolve, reject) => {
      writer.on("finish", resolve);
      writer.on("error", reject);
    });
  }

  const FormData = require("form-data");

  const formData = new FormData();
  const fileSize = `${fileSizeInMB(file)}MB`;

  formData.append("file", fs.createReadStream(file));
  const client = axios.create({
    baseURL: baseURL,
    headers: {
      Authorization: `Bearer ${accessToken}`,
      Accept: `application/json,text/*;q=0.99`,
    },
    maxContentLength: Infinity,
    maxBodyLength: Infinity,
  });
  let statusUrl = null;
  let uploadRes = null;

  try {
    log(`Uploading file '${file}' (size ${fileSize})`, "⏫");
    uploadRes = await client.post(`convert/${targetFormat}`, formData, {
      headers: formData.getHeaders(),
    });
    statusUrl = uploadRes.data.status_url;
  } catch (e) {
    if (
      e.response &&
      e.response.data &&
      e.response.data.response_code === 409
    ) {
      statusUrl = e.response.data.status_url;
    }
    if (!statusUrl) {
      throw e;
    }
  }

  

  const checkForConversionStatusAndDownloadIfConverted = async () => {
    let res = null;
    try {
      res = await client.get(statusUrl);
    } catch (e) {
      handleError(e, false);
    }
    if (!res || !statusUrl) {
      if (res && res.data) {
        console.error(res.data);
      }
      displayErrorMessageAndExit('Could not start conversion: res or statusUrl are missing... maybe there is something wrong with the api service?');
    }
    if (res.data.status && res.data.status !== lastDisplayedStatus) {
      lastDisplayedStatus = res.data.status;
      console.log("");
    } else {
      process.stdout.clearLine();
      process.stdout.cursorTo(0);
    }
    let statusIcon = {
      queued: "🗳",
      processing: "⚙️",
      done: "📦",
    };

    if (!res.data.status) {
      displayErrorMessageAndExit('No conversion status available… Please try again. Exiting now');
    }

    log(res.data.status, statusIcon[res.data.status], "process.stdout.write");

    if (res.data.status === "done") {
      // download file
      clearInterval(intervalID);
      let downloadUrl = res.data.download_url;

      if (typeof window !== "undefined") {
        throw new Error("To be implemented");
      } else {
        await downloadFile(client, downloadUrl, targetFile);
        log(`Downloaded to -> ${targetFile}`, `\n🦄`);
      }
    }
  }

  let lastDisplayedStatus = null;
  let intervalID = setInterval(
    checkForConversionStatusAndDownloadIfConverted,
    2000
  );
  checkForConversionStatusAndDownloadIfConverted()
};

(async () => {
  try {
    let targetFile =
      require("path").dirname(file) +
      "/" +
      require("path")
        .basename(file)
        .replace(/\.([^\.]+?)$/, "") +
      "." +
      targetFormat.toLowerCase();
    if (fs.existsSync(targetFile)) {
      console.log(`File '${targetFile}' already exists`);
      process.exit(1);
    }
    await convertFile(baseURL, accessToken, targetFormat, file, targetFile);
  } catch (e) {
    handleError(e, true);
  }
})();
