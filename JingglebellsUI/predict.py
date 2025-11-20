#!/usr/bin/env python3
"""
Python script to run predictions using DistilBERT models
Called from PHP to perform fake review detection and sentiment analysis
"""

import sys
import json
import os
import warnings

# Suppress warnings and device messages
warnings.filterwarnings('ignore')
os.environ['TRANSFORMERS_VERBOSITY'] = 'error'

from transformers import pipeline

# Get the directory where this script is located
script_dir = os.path.dirname(os.path.abspath(__file__))

# Model paths
model_fake_path = os.path.join(script_dir, "distil_fake")
model_sent_path = os.path.join(script_dir, "distil_sent")

# Initialize pipelines
try:
    # Suppress stdout temporarily during model loading
    import io
    from contextlib import redirect_stdout, redirect_stderr
    
    # Redirect stderr to suppress device messages
    f = io.StringIO()
    with redirect_stderr(f):
        pipe_fake = pipeline(
            "text-classification",
            model=model_fake_path,
            tokenizer=model_fake_path,
            top_k=None,
            max_length=512,
            truncation=True
        )
        
        pipe_sent = pipeline(
            "text-classification",
            model=model_sent_path,
            tokenizer=model_sent_path,
            top_k=None,
            max_length=512,
            truncation=True
        )
except Exception as e:
    print(json.dumps({"error": f"Model loading error: {str(e)}"}))
    sys.exit(1)

def predict_fake(text):
    """Predict if review is fake or real"""
    try:
        import io
        from contextlib import redirect_stderr
        f = io.StringIO()
        with redirect_stderr(f):
            result = pipe_fake(text)[0]
        # Get the label with highest score
        best_result = max(result, key=lambda x: x['score'])
        label_raw = best_result['label']
        score = best_result['score']
        
        # Convert label: LABEL_0 = REAL, LABEL_1 = FAKE
        label = "REAL" if label_raw == "LABEL_0" else "FAKE"
        
        return {
            "label": label,
            "confidence": round(score, 4),
            "raw_label": label_raw,
            "all_scores": {item['label']: round(item['score'], 4) for item in result}
        }
    except Exception as e:
        return {"error": f"Fake prediction error: {str(e)}"}

def predict_sentiment(text):
    """Predict sentiment (positive, negative) - excludes neutral"""
    try:
        import io
        from contextlib import redirect_stderr
        f = io.StringIO()
        with redirect_stderr(f):
            result = pipe_sent(text)[0]
        
        # Filter out neutral (LABEL_1) and get the best between positive and negative
        positive_negative_results = [r for r in result if r['label'] != 'LABEL_1']
        
        if not positive_negative_results:
            # Fallback: if somehow no positive/negative, use the best overall
            best_result = max(result, key=lambda x: x['score'])
        else:
            # Get the best between positive and negative
            best_result = max(positive_negative_results, key=lambda x: x['score'])
        
        label_raw = best_result['label']
        score = best_result['score']
        
        # Convert label: LABEL_0 = NEGATIVE, LABEL_2 = POSITIVE (no neutral)
        label_map = {
            "LABEL_0": "NEGATIVE",
            "LABEL_2": "POSITIVE"
        }
        label = label_map.get(label_raw, "UNKNOWN")
        
        # Calculate confidence relative to positive+negative only (normalize)
        pos_neg_total = sum(r['score'] for r in result if r['label'] in ['LABEL_0', 'LABEL_2'])
        if pos_neg_total > 0:
            normalized_score = score / pos_neg_total
        else:
            normalized_score = score
        
        # Filter all_scores to only show positive and negative
        filtered_scores = {item['label']: round(item['score'], 4) for item in result if item['label'] != 'LABEL_1'}
        
        return {
            "label": label,
            "confidence": round(normalized_score, 4),
            "raw_label": label_raw,
            "all_scores": filtered_scores
        }
    except Exception as e:
        return {"error": f"Sentiment prediction error: {str(e)}"}

def main():
    """Main function to handle command line input"""
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No text provided"}))
        sys.exit(1)
    
    # Get text from command line argument
    text = sys.argv[1]
    
    if not text or len(text.strip()) == 0:
        print(json.dumps({"error": "Empty text provided"}))
        sys.exit(1)
    
    # Run predictions
    fake_result = predict_fake(text)
    sent_result = predict_sentiment(text)
    
    # Return JSON response
    response = {
        "fake_detection": fake_result,
        "sentiment_analysis": sent_result,
        "text": text[:200] + "..." if len(text) > 200 else text  # Truncate for display
    }
    
    print(json.dumps(response, indent=2))

if __name__ == "__main__":
    main()

