import { Button, Result } from 'antd';

export default function ErrorState({ description, onRetry }) {
  return (
    <Result
      status="error"
      title="Gagal memuat data"
      subTitle={description}
      extra={
        <Button type="primary" onClick={onRetry}>
          Muat Ulang
        </Button>
      }
    />
  );
}
