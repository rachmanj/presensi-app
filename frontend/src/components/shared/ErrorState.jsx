import { Button, Result } from 'antd';

export default function ErrorState({ description, onRetry }) {
  return (
    <Result
      status="error"
      title="Failed to load data"
      subTitle={description}
      extra={
        <Button type="primary" onClick={onRetry}>
          Reload
        </Button>
      }
    />
  );
}
