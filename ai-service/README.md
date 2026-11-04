# AI Service - Future Integration Architecture

This directory is prepared for future AI/ML integration with the LifeLeft application. The current PHP backend is designed to be easily extensible for AI-powered features.

## Planned AI Features

### 1. OCR Expiry Date Extraction
Automatically extract expiry dates from uploaded documents using OCR technology.

**Implementation Approach:**
- Integrate Tesseract OCR or cloud-based OCR service (Google Vision API, AWS Textract)
- Process uploaded documents asynchronously
- Extract dates, document types, and key information
- Auto-populate form fields with extracted data

**API Endpoint Design:**
```
POST /api/ai/extract-document
{
  "document_id": 123,
  "file_path": "/uploads/user/123/document.pdf"
}

Response:
{
  "success": true,
  "extracted_data": {
    "expiry_date": "2025-12-31",
    "issue_date": "2020-01-01",
    "document_type": "passport",
    "confidence": 0.95
  }
}
```

### 2. Automatic Document Categorization
Use machine learning to automatically categorize uploaded documents.

**Implementation Approach:**
- Train a classification model on existing categorized documents
- Use document content, file names, and metadata
- Suggest categories to users with confidence scores

**API Endpoint Design:**
```
POST /api/ai/classify-document
{
  "document_id": 123,
  "title": "Passport Renewal",
  "description": "Indian passport expiry"
}

Response:
{
  "success": true,
  "suggestions": [
    {
      "category_id": 1,
      "category_name": "Identity Documents",
      "confidence": 0.92
    },
    {
      "category_id": 10,
      "category_name": "Other",
      "confidence": 0.08
    }
  ]
}
```

### 3. Intelligent Reminder Recommendations
AI-powered reminder scheduling based on user behavior and document importance.

**Implementation Approach:**
- Analyze user's renewal patterns
- Identify critical documents that need early reminders
- Suggest optimal reminder timing
- Learn from user feedback on reminder effectiveness

**API Endpoint Design:**
```
POST /api/ai/optimize-reminders
{
  "user_id": 1
}

Response:
{
  "success": true,
  "recommendations": [
    {
      "record_id": 45,
      "current_reminder_days": 7,
      "suggested_reminder_days": 14,
      "reason": "High-value document with complex renewal process"
    }
  ]
}
```

### 4. Monthly AI Summaries
Generate intelligent summaries of user's expiry and renewal activity.

**Implementation Approach:**
- Use LLM (Large Language Model) to generate natural language summaries
- Highlight important expirations, spending trends, and recommendations
- Personalize based on user's priorities

**API Endpoint Design:**
```
POST /api/ai/generate-summary
{
  "user_id": 1,
  "month": "2024-01",
  "include_recommendations": true
}

Response:
{
  "success": true,
  "summary": "This month you have 3 documents expiring. Your passport renewal is the most urgent...",
  "recommendations": [
    "Consider renewing your vehicle insurance early for a 10% discount",
    "Your subscription costs have increased by 15% compared to last month"
  ]
}
```

### 5. Renewal Cost Predictions
Predict future renewal costs based on historical data and market trends.

**Implementation Approach:**
- Analyze historical renewal costs
- Factor in inflation, market trends, and seasonal patterns
- Provide cost estimates for upcoming renewals
- Help users budget effectively

**API Endpoint Design:**
```
POST /api/ai/predict-costs
{
  "user_id": 1,
  "record_id": 45,
  "months_ahead": 6
}

Response:
{
  "success": true,
  "predictions": [
    {
      "month": "2024-06",
      "predicted_cost": 2500.00,
      "confidence": 0.85,
      "factors": ["inflation", "historical_trend"]
    }
  ]
}
```

## Architecture for AI Integration

### Option 1: Python Microservice
Run AI services as a separate Python Flask/FastAPI microservice.

**Pros:**
- Access to extensive Python ML libraries (scikit-learn, TensorFlow, PyTorch)
- Isolated from PHP backend
- Can scale independently
- Better for heavy ML workloads

**Cons:**
- Additional infrastructure complexity
- Need to manage service communication
- Deployment overhead

**Implementation:**
```python
# ai-service/app.py
from flask import Flask, request, jsonify
import ocr_processor
import classifier

app = Flask(__name__)

@app.route('/extract', methods=['POST'])
def extract_document():
    file_path = request.json['file_path']
    result = ocr_processor.extract(file_path)
    return jsonify(result)

@app.route('/classify', methods=['POST'])
def classify_document():
    data = request.json
    result = classifier.predict(data)
    return jsonify(result)
```

### Option 2: PHP-ML Integration
Use PHP-ML library for machine learning within the PHP application.

**Pros:**
- No additional services needed
- Simpler deployment
- Consistent technology stack

**Cons:**
- Limited ML capabilities compared to Python
- Performance limitations for complex models
- Smaller ecosystem

**Implementation:**
```php
// Use php-ml library
use Phpml\Classification\NaiveBayes;
use Phpml\FeatureExtraction\TokenCountVectorizer;

$classifier = new NaiveBayes();
$classifier->train($trainingSamples, $trainingLabels);
$predicted = $classifier->predict($sample);
```

### Option 3: Cloud AI Services
Use cloud-based AI services (Google Cloud AI, AWS AI Services, Azure AI).

**Pros:**
- No ML infrastructure needed
- State-of-the-art models
- Scalable and reliable
- Pay-as-you-go pricing

**Cons:**
- Ongoing costs
- Data privacy considerations
- Dependency on external services

**Implementation:**
```php
// Google Cloud Vision API
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

$client = new ImageAnnotatorClient();
$response = $client->documentTextDetection($imageUri);
```

## Database Schema Extensions

### New Tables for AI Features

```sql
-- AI processing queue
CREATE TABLE ai_processing_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_id INT NOT NULL,
    task_type VARCHAR(50) NOT NULL, -- 'ocr', 'classify', 'summarize'
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
    input_data JSON,
    result_data JSON,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES expiry_records(id) ON DELETE CASCADE
);

-- AI model training data
CREATE TABLE ai_training_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    category_id INT NOT NULL,
    features JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES expiry_records(id) ON DELETE CASCADE
);

-- AI recommendations
CREATE TABLE ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_id INT,
    recommendation_type VARCHAR(50) NOT NULL,
    recommendation_data JSON,
    is_accepted TINYINT(1) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES expiry_records(id) ON DELETE SET NULL
);
```

## Integration Points

### 1. Document Upload Hook
When a user uploads a document, trigger AI processing:

```php
// In records/add.php after successful upload
if ($upload_result['success']) {
    // Queue AI processing
    $stmt = $pdo->prepare("
        INSERT INTO ai_processing_queue (user_id, record_id, task_type, input_data)
        VALUES (?, ?, 'ocr', ?)
    ");
    $stmt->execute([$user_id, $record_id, json_encode(['file_path' => $upload_result['path']])]);
}
```

### 2. Background Processing
Create a background worker to process AI tasks:

```php
// ai-service/worker.php
while (true) {
    $stmt = $pdo->query("
        SELECT * FROM ai_processing_queue 
        WHERE status = 'pending' 
        ORDER BY created_at ASC 
        LIMIT 5
    ");
    $tasks = $stmt->fetchAll();
    
    foreach ($tasks as $task) {
        process_ai_task($task);
    }
    
    sleep(10);
}
```

### 3. API Endpoints
Create REST API endpoints for AI features:

```php
// api/ai/extract.php
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/includes/auth.php';

requireLogin();

// Call AI service (Python microservice or cloud API)
$result = call_ai_service('extract', ['file_path' => $file_path]);

echo json_encode($result);
```

## Security Considerations

1. **Data Privacy**: Ensure sensitive documents are processed securely
2. **API Keys**: Store AI service API keys securely (environment variables)
3. **Rate Limiting**: Implement rate limiting for AI API calls
4. **User Consent**: Get user consent before using AI features
5. **Data Retention**: Define policies for AI-processed data retention

## Performance Considerations

1. **Async Processing**: Use background workers for heavy AI tasks
2. **Caching**: Cache AI results to avoid redundant processing
3. **Queue Management**: Implement proper queue management for AI tasks
4. **Resource Limits**: Set limits on AI processing to prevent resource exhaustion

## Testing AI Features

1. **Unit Tests**: Test individual AI components
2. **Integration Tests**: Test AI integration with PHP backend
3. **User Acceptance Testing**: Test AI features with real users
4. **A/B Testing**: Compare AI suggestions vs manual input

## Rollout Strategy

1. **Phase 1**: Implement OCR for document extraction
2. **Phase 2**: Add automatic categorization
3. **Phase 3**: Deploy reminder optimization
4. **Phase 4**: Launch monthly summaries
5. **Phase 5**: Add cost predictions

## Monitoring and Analytics

Track AI feature usage and effectiveness:

- Processing success rates
- User acceptance of recommendations
- Time saved by automation
- Cost vs benefit analysis

## Conclusion

This architecture provides a flexible foundation for integrating AI capabilities into LifeLeft. The modular design allows for gradual implementation and easy swapping of AI providers (local vs cloud) as needs evolve.
