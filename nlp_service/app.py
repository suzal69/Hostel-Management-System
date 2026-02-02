from flask import Flask, request, jsonify
import os

app = Flask(__name__)
MODEL_PATH = os.environ.get('MODEL_PATH', 'model.joblib')

try:
    import joblib
    clf = joblib.load(MODEL_PATH)
except Exception:
    clf = None


@app.route('/classify', methods=['POST'])
def classify():
    data = request.json or {}
    text = data.get('text', '')
    hostel_id = data.get('hostel_id')

    # Simple fallback heuristics if no model available
    if not clf:
        text_l = text.lower()
        urgency = 'low'
        score = 0.0
        topic = 'general'
        if 'fire' in text_l or 'gas' in text_l or 'electric' in text_l:
            urgency = 'high'
            score = 5.0
            topic = 'safety'
        elif 'leak' in text_l or 'broken' in text_l:
            urgency = 'medium'
            score = 3.0
            topic = 'plumbing'
        return jsonify({'urgency': urgency, 'topic': topic, 'score': score, 'suggested_manager_id': None})

    # If a trained model exists, adapt this to your model's predict signature
    try:
        pred = clf.predict([text])[0]
        # Expect model to return dict-like object or array; adapt as needed
        return jsonify(pred)
    except Exception as e:
        return jsonify({'urgency': 'low', 'topic': 'general', 'score': 0.0, 'suggested_manager_id': None, 'error': str(e)})


if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000)
