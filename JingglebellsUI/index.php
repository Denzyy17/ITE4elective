<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Analyzer - Fake Detection & Sentiment Analysis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            padding: 40px;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        .input-section {
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 1.1em;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .char-count {
            text-align: right;
            color: #999;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        button {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-analyze {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-analyze:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-clear {
            background: #f5f5f5;
            color: #333;
        }

        .btn-clear:hover {
            background: #e0e0e0;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .results {
            display: none;
            margin-top: 30px;
        }

        .results.active {
            display: block;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid;
        }

        .result-card.fake-real {
            border-left-color: #28a745;
        }

        .result-card.fake-fake {
            border-left-color: #dc3545;
        }

        .result-card.sentiment-positive {
            border-left-color: #28a745;
        }

        .result-card.sentiment-negative {
            border-left-color: #dc3545;
        }

        .result-title {
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }

        .result-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .result-label {
            font-size: 1.5em;
            font-weight: 700;
        }

        .result-label.real {
            color: #28a745;
        }

        .result-label.fake {
            color: #dc3545;
        }

        .result-label.positive {
            color: #28a745;
        }

        .result-label.negative {
            color: #dc3545;
        }

        .confidence-bar {
            flex: 1;
            min-width: 200px;
        }

        .confidence-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9em;
            color: #666;
        }

        .confidence-fill {
            height: 25px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            transition: width 0.5s ease-out;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9em;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 5px solid #dc3545;
            display: none;
        }

        .error.active {
            display: block;
        }

        .details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 0.9em;
            color: #666;
        }

        .details-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .details-content {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .detail-item {
            background: white;
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Review Analyzer</h1>
        <p class="subtitle">Detect Fake Reviews & Analyze Sentiment with AI</p>

        <div class="input-section">
            <label for="reviewText">Enter Review Text:</label>
            <textarea id="reviewText" placeholder="Paste or type your review here..."></textarea>
            <div class="char-count">
                <span id="charCount">0</span> / 5000 characters
            </div>
        </div>

        <div class="button-group">
            <button class="btn-analyze" id="analyzeBtn" onclick="analyzeReview()">Analyze Review</button>
            <button class="btn-clear" onclick="clearForm()">Clear</button>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Analyzing review...</p>
        </div>

        <div class="error" id="error"></div>

        <div class="results" id="results">
            <div class="result-card" id="fakeResult">
                <div class="result-title">🎯 Fake Review Detection</div>
                <div class="result-content">
                    <div class="result-label" id="fakeLabel">-</div>
                    <div class="confidence-bar">
                        <div class="confidence-label">
                            <span>Confidence</span>
                            <span id="fakeConfidence">-</span>
                        </div>
                        <div class="confidence-fill" id="fakeBar" style="width: 0%">0%</div>
                    </div>
                </div>
                <div class="details" id="fakeDetails"></div>
            </div>

            <div class="result-card" id="sentimentResult">
                <div class="result-title">💭 Sentiment Analysis</div>
                <div class="result-content">
                    <div class="result-label" id="sentimentLabel">-</div>
                    <div class="confidence-bar">
                        <div class="confidence-label">
                            <span>Confidence</span>
                            <span id="sentimentConfidence">-</span>
                        </div>
                        <div class="confidence-fill" id="sentimentBar" style="width: 0%">0%</div>
                    </div>
                </div>
                <div class="details" id="sentimentDetails"></div>
            </div>
        </div>
    </div>

    <script>
        const reviewTextarea = document.getElementById('reviewText');
        const charCount = document.getElementById('charCount');
        const analyzeBtn = document.getElementById('analyzeBtn');
        const loading = document.getElementById('loading');
        const results = document.getElementById('results');
        const error = document.getElementById('error');

        // Character counter
        reviewTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 5000) {
                charCount.style.color = '#dc3545';
            } else {
                charCount.style.color = '#999';
            }
        });

        function clearForm() {
            reviewTextarea.value = '';
            charCount.textContent = '0';
            results.classList.remove('active');
            error.classList.remove('active');
            loading.classList.remove('active');
        }

        async function analyzeReview() {
            const text = reviewTextarea.value.trim();
            
            if (!text) {
                showError('Please enter a review text before analyzing.');
                return;
            }

            if (text.length > 5000) {
                showError('Review text is too long. Maximum 5000 characters.');
                return;
            }

            // Reset UI
            error.classList.remove('active');
            results.classList.remove('active');
            loading.classList.add('active');
            analyzeBtn.disabled = true;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ text: text })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Analysis failed');
                }

                // Display results
                displayResults(data);

            } catch (err) {
                showError('Error: ' + err.message);
            } finally {
                loading.classList.remove('active');
                analyzeBtn.disabled = false;
            }
        }

        function displayResults(data) {
            // Fake Detection Results
            const fakeData = data.fake_detection;
            const fakeLabel = document.getElementById('fakeLabel');
            const fakeConfidence = document.getElementById('fakeConfidence');
            const fakeBar = document.getElementById('fakeBar');
            const fakeResult = document.getElementById('fakeResult');
            const fakeDetails = document.getElementById('fakeDetails');

            if (fakeData.error) {
                showError('Fake detection error: ' + fakeData.error);
                return;
            }

            fakeLabel.textContent = fakeData.label;
            fakeLabel.className = 'result-label ' + fakeData.label.toLowerCase();
            
            const fakeConf = (fakeData.confidence * 100).toFixed(1);
            fakeConfidence.textContent = fakeConf + '%';
            fakeBar.style.width = fakeConf + '%';
            fakeBar.textContent = fakeConf + '%';

            fakeResult.className = 'result-card fake-' + fakeData.label.toLowerCase();

            // Fake details
            if (fakeData.all_scores) {
                let detailsHtml = '<div class="details-title">All Scores:</div><div class="details-content">';
                for (const [label, score] of Object.entries(fakeData.all_scores)) {
                    const labelName = label === 'LABEL_0' ? 'REAL' : 'FAKE';
                    detailsHtml += `<div class="detail-item">${labelName}: ${(score * 100).toFixed(1)}%</div>`;
                }
                detailsHtml += '</div>';
                fakeDetails.innerHTML = detailsHtml;
            }

            // Sentiment Analysis Results
            const sentData = data.sentiment_analysis;
            const sentimentLabel = document.getElementById('sentimentLabel');
            const sentimentConfidence = document.getElementById('sentimentConfidence');
            const sentimentBar = document.getElementById('sentimentBar');
            const sentimentResult = document.getElementById('sentimentResult');
            const sentimentDetails = document.getElementById('sentimentDetails');

            if (sentData.error) {
                showError('Sentiment analysis error: ' + sentData.error);
                return;
            }

            sentimentLabel.textContent = sentData.label;
            sentimentLabel.className = 'result-label ' + sentData.label.toLowerCase();
            
            const sentConf = (sentData.confidence * 100).toFixed(1);
            sentimentConfidence.textContent = sentConf + '%';
            sentimentBar.style.width = sentConf + '%';
            sentimentBar.textContent = sentConf + '%';

            sentimentResult.className = 'result-card sentiment-' + sentData.label.toLowerCase();

            // Sentiment details
            if (sentData.all_scores) {
                let detailsHtml = '<div class="details-title">All Scores:</div><div class="details-content">';
                const labelMap = {
                    'LABEL_0': 'NEGATIVE',
                    'LABEL_2': 'POSITIVE'
                };
                for (const [label, score] of Object.entries(sentData.all_scores)) {
                    // Only show positive and negative, skip neutral
                    if (label === 'LABEL_1') continue;
                    const labelName = labelMap[label] || label;
                    detailsHtml += `<div class="detail-item">${labelName}: ${(score * 100).toFixed(1)}%</div>`;
                }
                detailsHtml += '</div>';
                sentimentDetails.innerHTML = detailsHtml;
            }

            // Show results
            results.classList.add('active');
        }

        function showError(message) {
            error.textContent = message;
            error.classList.add('active');
        }

        // Allow Enter key to submit (Ctrl+Enter or Cmd+Enter)
        reviewTextarea.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                analyzeReview();
            }
        });
    </script>
</body>
</html>

